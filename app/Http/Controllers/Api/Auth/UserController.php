<?php

namespace App\Http\Controllers\Api\Auth;

use Stripe\Stripe;
use Stripe\Account;
use App\Models\User;
use App\Models\Artist;
use App\Helpers\Helper;
use App\Models\Festival;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\AllAlbumResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'username', 'slug', 'bio', 'country', 'sex', 'age', 'email', 'avatar'];
    }

    public function me()
    {
        $user = User::select($this->select)->find(auth('api')->id());

        if (!$user) {
            return Helper::jsonResponse(false, 'User not found', 404, null);
        }

        // Count albums/artists
        $user->total_albums = $user->artists()->count();

        // Count total festivals
        $user->total_festables = Festival::count();

        // ========== WISHLIST ==========
        $wishlistArtistIds = DB::table('wishlists')
            ->where('user_id', $user->id)
            ->pluck('artist_id');

        $wishlistArtists = Artist::whereIn('id', $wishlistArtistIds)
            ->with(['review', 'experiences', 'festival', 'documents'])
            ->get();

        $user->wishlist = AllAlbumResource::collection($wishlistArtists);

        // ========== TOP ARTISTS (NEW) ==========
        $topArtists = Artist::with(['review', 'experiences', 'festival', 'documents'])
            ->get()
            ->filter(function ($artist) {
                $avg = $artist->review->avg('rating');
                return $avg && $avg > 0;    
            })
            ->sortByDesc(function ($artist) {
                return $artist->review->avg('rating');  
            })
            ->take(10)
            ->values();

        $user->top_artists = AllAlbumResource::collection($topArtists);

        return Helper::jsonResponse(true, 'User details fetched successfully', 200, $user);
    }

    public function updateProfile(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'password' => 'nullable|string|min:6|confirmed',
            'bio' => 'nullable|string|max:1000',
            'country' => 'nullable|string|max:100',
            'sex' => 'nullable|string|max:10',
            'age' => 'nullable|integer|min:0',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else if (array_key_exists('password', $validatedData)) {
            unset($validatedData['password']);
        }

        $user = auth('api')->user();

        /** ✅ Username generator: only if user has no username yet OR name changed */
        if (!$user->username || $user->name !== $validatedData['name']) {
            $validatedData['username'] = '@' . strtolower($validatedData['name']) . '_' . $this->randomAlphaNum(4);
        }
        if ($request->hasFile('avatar')) {
            if (!empty($user->avatar)) {
                Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
            }
            $validatedData['avatar'] = Helper::fileUpload($request->file('avatar'), 'user/avatar', getFileName($request->file('avatar')));
        } else {
            $validatedData['avatar'] = $user->avatar;
        }

        $user->update($validatedData);

        $data = User::select($this->select)->find($user->id);
        return Helper::jsonResponse(true, 'Profile updated successfully', 200, $data);
    }

    private function randomAlphaNum($length = 4)
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }


    public function updateAvatar(Request $request)
    {
        $validatedData = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);
        $user = auth('api')->user();
        if (!empty($user->avatar)) {
            Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
        }
        $validatedData['avatar'] = Helper::fileUpload($request->file('avatar'), 'user/avatar', getFileName($request->file('avatar')));
        $user->update($validatedData);
        $data = User::select($this->select)->find($user->id);
        return Helper::jsonResponse(true, 'Avatar updated successfully', 200, $data);
    }

    public function delete()
    {
        $user = User::findOrFail(auth('api')->id());
        if (!empty($user->avatar) && file_exists(public_path($user->avatar))) {
            Helper::fileDelete(public_path($user->avatar));
        }
        Auth::logout('api');
        $user->delete();
        return Helper::jsonResponse(true, 'Profile deleted successfully', 200);
    }

    public function destroy()
    {
        $user = User::findOrFail(auth('api')->id());
        if (!empty($user->avatar) && file_exists(public_path($user->avatar))) {
            Helper::fileDelete(public_path($user->avatar));
        }
        Auth::logout('api');
        $user->forceDelete();
        return Helper::jsonResponse(true, 'Profile deleted successfully', 200);
    }



    public function changePassword(Request $request)
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return Helper::jsonResponse(false, 'User not found', 404, null);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'old_password'      => 'required',
            'new_password'      => 'required|min:6',
            'confirm_password'  => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return Helper::jsonResponse(false, 'Validation failed', 422, $validator->errors());
        }

        // Check if old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return Helper::jsonResponse(false, 'Old password does not match', 400, null);
        }

        // Update with new password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return Helper::jsonResponse(true, 'Password changed successfully', 200, null);
    }
}
