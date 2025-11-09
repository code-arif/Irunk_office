<?php

namespace App\Http\Controllers\Api\Frontend\Wishlist;

use App\Models\Artist;
use App\Helpers\Helper;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;

class WishlistController extends Controller
{
    public function wishlist($artist_id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $artist = Artist::where('id', $artist_id)->where('user_id', $user->id)->first();
        
        if($artist && $artist->user_id == $user->id){
            return Helper::jsonResponse(false, 'You cannot add your own artist to wishlist.', 403);
        }

        if (!$artist) {
            return Helper::jsonResponse(false, 'Artist not found.', 404);
        }

        $exists = Wishlist::where('user_id', $user->id)->where('artist_id', $artist_id)->first();

        if ($exists) {
            $exists->delete();
            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Removed from wishlist',
                'wishlist' => false
            ]);
        }

        Wishlist::create([
            'user_id'  => $user->id,
            'artist_id' => $artist_id,
        ]);

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Added to wishlist',
            'wishlist' => true
        ]);
    }

    // get wishlist items
    public function getWishlistItems()
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $wishlistItems = Wishlist::where('user_id', $user->id)->with('artist')->get();
        if ($wishlistItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'No wishlist items found.',
                'data'    => []
            ]);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'data'    => WishlistResource::collection($wishlistItems),
        ]);
    }
}
