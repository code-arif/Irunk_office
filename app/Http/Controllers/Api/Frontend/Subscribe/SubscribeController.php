<?php

namespace App\Http\Controllers\Api\Frontend\Subscribe;

use Exception;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SubscribeController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Invalid email address",
                'data' => $validator->errors(),
                'code' => 422
            ]);
        }

        try {
            $subscriber = Subscriber::firstOrCreate(['email' => $request->input('email')]);

            return response()->json([
                'success' => true,
                'message' => "Subscriber created successfully",
                'data' => $subscriber,
                'code' => 200
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'code' => 500
            ]);
        }
    }
}
