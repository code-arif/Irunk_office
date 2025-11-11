<?php

namespace App\Http\Controllers\Api\Frontend\Review;

use App\Models\Artist;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\CommentLike;
use Illuminate\Http\Request;
use App\Models\ReviewComment;
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
            return response()->json(['status'  => false, 'message' => 'Validation failed', 'error'   => $validator->errors()], 404);
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


    // Review comment

    public function commentOnReview(Request $request)
    {
        $user = auth()->guard('api')->user();

        $request->validate([
            'review_id' => 'required|exists:reviews,id',
            'comment'   => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:review_comments,id'
        ]);

        $review = Review::find($request->review_id);

        // Owner cannot comment on own review (but can reply)
        if ($review->user_id === $user->id && !$request->parent_id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot comment on your own review!'
            ], 403);
        }

        $comment = ReviewComment::create([
            'review_id' => $review->id,
            'user_id'   => $user->id,
            'comment'   => $request->comment,
            'parent_id' => $request->parent_id
        ]);

        return response()->json([
            'status' => true,
            'message' => $request->parent_id ? 'Reply added' : 'Comment added',
            'data' => $comment
        ]);
    }

    // Like a review
    public function likeReview($reviewId)
    {
        $user = auth()->guard('api')->user();
        $review = Review::findOrFail($reviewId);

        $like = ReviewLike::where('review_id', $reviewId)
            ->where('user_id', $user->id)
            ->first();

        if ($like) {
            $like->delete();
            return response()->json(['status' => true, 'code' => 200, 'message' => 'Review unliked successfully!']);
        }

        ReviewLike::create(['review_id' => $reviewId, 'user_id' => $user->id]);

        return response()->json(['status' => true, 'code' => 200, 'message' => 'Review liked successfully!']);
    }

    // Like a comment
    public function likeComment($commentId)
    {
        $user = auth()->guard('api')->user();
        $comment = ReviewComment::findOrFail($commentId);

        $like = CommentLike::where('comment_id', $commentId)
            ->where('user_id', $user->id)
            ->first();

        if ($like) {
            $like->delete();
            return response()->json(['status' => true, 'code' => 200, 'message' => 'Comment unliked successfully!']);
        }

        CommentLike::create(['comment_id' => $commentId, 'user_id' => $user->id]);

        return response()->json(['status' => true, 'code' => 200, 'message' => 'Comment liked successfully!']);
    }
}
