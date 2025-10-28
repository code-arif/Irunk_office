<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'email', 'avatar'];   
    }

    public function RedirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function HandleProviderCallback($provider)
    {
        $data = Socialite::driver($provider)->stateless()->user();
        return $data;
    }

public function SocialLogin(Request $request)
{
    $request->validate([
        'token'    => 'required',
        'provider' => 'required|in:google', // Only Google allowed
        'role'     => 'nullable|string'
    ]);

    try {
        $provider = $request->provider;

        // Fetch user from Google using token
        $socialUser = Socialite::driver($provider)
            ->stateless()
            ->userFromToken($request->token);

        if (!$socialUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Check if user exists including soft deleted
        $user = User::withTrashed()->where('email', $socialUser->getEmail())->first();

        if ($user && !empty($user->deleted_at)) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account has been deleted.'
            ], 410);
        }

        $isNewUser = false;

        if (!$user) {
            // Generate unique slug
            $slug = "user_" . rand(1000000000, 9999999999);
            while (User::where('slug', $slug)->exists()) {
                $slug = "user_" . rand(1000000000, 9999999999);
            }

            $password = Str::random(16);

            // Create new user
            $user = User::create([
                'name'            => $socialUser->getName() ?? 'Google User',
                'email'           => $socialUser->getEmail(),
                'password'        => bcrypt($password),
                'avatar'          => $socialUser->getAvatar(),
                'status'          => 'active',
                'slug'            => $slug,
                'otp_verified_at' => now(),
            ]);

            if ($request->filled('role')) {
                $user->assignRole($request->input('role'));
            }

            $isNewUser = true;
        } else {
            // Update otp_verified_at for existing users
            $user->update([
                'otp_verified_at' => now(),
            ]);
        }

        // Login and generate JWT token
        Auth::login($user);
        $token = auth('api')->login($user);
        $data = User::with('roles')->find($user->id);

        return response()->json([
            'status'     => true,
            'message'    => $isNewUser ? 'User registered successfully.' : 'User logged in successfully.',
            'code'       => 200,
            'token_type' => 'bearer',
            'token'      => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'data'       => $data
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Something went wrong',
            'error'   => $e->getMessage()
        ], 500);
    }
}


}
