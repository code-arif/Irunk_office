<?php

namespace App\Http\Controllers\Api\Frontend\Review;

use App\Models\User;
use App\Models\Order;
use App\Models\Artist;
use App\Models\Review;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\UserReview;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function review(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status'   => false,
                'message'  => 'Unauthorized'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'artist_id' => 'required|exists:artists,id',
            'rating'   => 'required|string|min:1|max:5',
            'comment'  => 'nullable|string|max:1000'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'error'   => $validator->errors()
            ], 404);
        }

        $artist = Artist::where('id', $request->artist_id)->first();

        if ($artist->user_id === $user->id) {
            return response()->json([
                'status'   => false,
                'message'  => 'You could not review your own album!'
            ], 402);
        }

        $review = Review::where('artist_id', $request->artist_id)->where('user_id', $user->id)->first();

        if ($review) {
            return response()->json([
                'status'    => false,
                'message'   => 'You already reviews this album.'
            ], 404);
        }



        $review = Review::create([
            'artist_id'  => $request->artist_id,
            'user_id'     => $user->id,
            'rating'      => $request->rating,
            'comment'     => $request->comment ?? ''
        ]);


        return response()->json([
            'status' => true,
            'message' => 'Review submitted successfully',
            'data' => $review
        ]);
    }


    // show existing review
    public function showExistingReview($slug)
    {
        $authUser = auth()->guard('api')->user();

        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Find the user whose reviews we want to show
        $targetUser = User::where('slug', $slug)->first();

        if (!$targetUser) {
            return response()->json([
                'status'          => false,
                'message'         => 'User not found.',
                'data'            => [],
                'average_rating'  => 0,
                'total_reviews'   => 0,
            ], 404);
        }

        // Get all product IDs for the target user
        $productIds = Product::where('user_id', $targetUser->id)->pluck('id');

        if ($productIds->isEmpty()) {
            return response()->json([
                'status'          => false,
                'message'         => 'No products found for this user.',
                'data'            => [],
                'average_rating'  => 0,
                'total_reviews'   => 0,
            ]);
        }

        // Pagination size (you can set default or allow query param)
        $perPage = $request->per_page ?? 10;

        // Fetch paginated reviews with reviewer info
        $reviews = Review::whereIn('product_id', $productIds)
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate($perPage);

        // Calculate average rating and total reviews (from all, not just paginated)
        $allReviews = Review::whereIn('product_id', $productIds)->get();
        $averageRating = $allReviews->avg('rating') ?? 0;
        $totalReviews  = $allReviews->count();

        // Format paginated data
        $reviewData = $reviews->map(function ($review) {
            return [
                'user_name'   => optional($review->user)->name,
                'user_avatar' => $review->user && $review->user->avatar
                    ? asset($review->user->avatar)
                    : null,
                'rating'      => $review->rating,
                'comment'     => $review->comment,
            ];
        });

        return response()->json([
            'status'          => true,
            'message'         => 'User’s product reviews fetched successfully.',
            'data'            => $reviewData,
            'total_reviews'   => $totalReviews,
            'average_rating'  => round($averageRating, 2),
            'current_page'    => $reviews->currentPage(),
            'current_page'    => $reviews->currentPage(),
            'next_page_url'   => $reviews->nextPageUrl(),
            'last_page'       => $reviews->lastPage(),
            'per_page'        => $reviews->perPage(),
            'total'           => $reviews->total(),

        ]);
    }


    // my products review  

    public function ownProductsReview(Request $request)
    {
        $authUser = auth()->guard('api')->user();

        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get all product IDs owned by the user
        $productIds = Product::where('user_id', $authUser->id)->pluck('id');

        // Fetch reviews for those products with pagination and reviewer info
        $reviews = Review::whereIn('product_id', $productIds)
            ->with(['user:id,name,avatar']) // load reviewer info
            ->latest()
            ->paginate($request->get('per_page', 10)); // default 10 per page

        if ($reviews->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No reviews found!',
                'code'   => 404
            ]);
        }

        // Format reviews
        $reviewData = $reviews->map(function ($review) {
            return [
                'user_name'   => optional($review->user)->name,
                'user_avatar' => $review->user && $review->user->avatar
                    ? asset($review->user->avatar)
                    : null,
                'rating'      => $review->rating,
                'comment'     => $review->comment,
                'created_at'  => $review->created_at->format('jS M, Y'),
            ];
        });

        return response()->json([
            'status'          => true,
            'message'         => 'Reviews fetched successfully',
            'reviews'         => $reviewData,
            'current_page'    => $reviews->currentPage(),
            'next_page_url'   => $reviews->nextPageUrl(),
            'prev_page_url'   => $reviews->previousPageUrl(),
            'last_page'       => $reviews->lastPage(),
            'per_page'        => $reviews->perPage(),
            'total'           => $reviews->total(),
        ]);
    }


    // user profile review

    public function userReview(Request $request)
    {
        $authUser = auth()->guard('api')->user();

        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'seller_slug' => 'required|string|exists:users,slug',
            'rating'      => 'required|numeric|min:1|max:5',
            'comment'     => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'error'   => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Find seller by slug
        $seller = User::where('slug', $validated['seller_slug'])->first();

        // Check if user has purchased any item from this seller
        $hasBought = OrderItem::where('seller_id', $seller->id)
            ->whereHas('order', function ($query) use ($authUser) {
                $query->where('buyer_id', $authUser->id);
            })
            ->exists();

        if (!$hasBought) {
            return response()->json([
                'status'  => false,
                'message' => 'You cannot review this seller because you have not purchased any product from them.',
            ], 403);
        }

        // Check if user already reviewed this seller
        $alreadyReviewed = UserReview::where('buyer_id', $authUser->id)
            ->where('seller_id', $seller->id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'status'  => false,
                'message' => 'You have already reviewed this seller.',
            ], 403);
        }

        // Save the review
        $review = UserReview::create([
            'buyer_id'  => $authUser->id,
            'seller_id' => $seller->id,
            'rating'    => $validated['rating'],
            'comment'   => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Review submitted successfully.',
            'data'    => $review,
        ], 200);
    }

    // get UserReview

    public function OwnUserReviews(Request $request)
    {
        $authUser = auth()->guard('api')->user();

        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Fetch reviews where the authenticated user is the buyer
        $reviews = UserReview::where('seller_id', $authUser->id)
            ->with(['seller:id,name,avatar']) // load seller info
            ->latest()
            ->paginate($request->get('per_page', 10));

        if ($reviews->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No reviews found!',
                'code'   => 404
            ]);
        }

        // Format reviews
        $reviewData = $reviews->map(function ($review) {
            return [
                'seller_name'   => optional($review->seller)->name,
                'seller_avatar' => $review->seller && $review->seller->avatar
                    ? asset($review->seller->avatar)
                    : null,
                'rating'        => $review->rating,
                'comment'       => $review->comment,
                'created_at'    => $review->created_at->format('jS M, Y'),
            ];
        });

        return response()->json([
            'status'          => true,
            'message'         => 'Your reviews fetched successfully',
            'reviews'         => $reviewData,
            'current_page'    => $reviews->currentPage(),
            'next_page_url'   => $reviews->nextPageUrl(),
            'prev_page_url'   => $reviews->previousPageUrl(),
            'last_page'       => $reviews->lastPage(),
            'per_page'        => $reviews->perPage(),
            'total'           => $reviews->total(),
        ]);
    }



    public function showUserExistingReview(Request $request)
    {
        $authUser = auth()->guard('api')->user();

        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get slug from query param
        $slug = $request->query('slug');

        if (!$slug) {
            return response()->json([
                'status'  => false,
                'message' => 'Slug parameter is required.',
                'data'    => [],
                'average_rating' => 0,
                'total_reviews'  => 0,
            ], 422);
        }

        // Find seller by slug
        $seller = User::where('slug', $slug)->first();

        if (!$seller) {
            return response()->json([
                'status'          => false,
                'message'         => 'User not found.',
                'data'            => [],
                'average_rating'  => 0,
                'total_reviews'   => 0,
            ], 404);
        }

        // Pagination (default 10 per page)
        $perPage = $request->get('per_page', 10);

        // Fetch reviews for this seller
        $reviews = UserReview::where('seller_id', $seller->id)
            ->with('buyer:id,name,avatar')
            ->latest()
            ->paginate($perPage);

        // Calculate average rating and total reviews
        $averageRating = UserReview::where('seller_id', $seller->id)->avg('rating') ?? 0;
        $totalReviews  = UserReview::where('seller_id', $seller->id)->count();

        // Transform paginated reviews
        $reviewData = $reviews->getCollection()->map(function ($review) {
            return [
                'buyer_name'   => optional($review->buyer)->name,
                'buyer_avatar' => $review->buyer && $review->buyer->avatar
                    ? asset($review->buyer->avatar)
                    : null,
                'rating'       => $review->rating,
                'comment'      => $review->comment,
                'created_at'   => $review->created_at->format('jS M, Y'),
            ];
        });

        // Replace collection with transformed data
        $reviews->setCollection($reviewData);

        return response()->json([
            'status'          => true,
            'message'         => 'Seller reviews fetched successfully.',
            'data'            => $reviews->items(),
            'total_reviews'   => $totalReviews,
            'average_rating'  => round($averageRating, 2),
            'current_page'    => $reviews->currentPage(),
            'next_page_url'   => $reviews->nextPageUrl(),
            'last_page'       => $reviews->lastPage(),
            'per_page'        => $reviews->perPage(),
            'total'           => $reviews->total(),
        ]);
    }
}
