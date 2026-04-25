<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FriendListResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FriendsController extends Controller
{
    use ApiResponse;

    // list of all auth user friend
    public function friendList()
    {
        $user = auth('api')->user();

        // friend IDs collect from both side
        $friendIds = DB::table('friends')
            ->where('user_id', $user->id)
            ->pluck('friend_id')
            ->merge(
                DB::table('friends')
                    ->where('friend_id', $user->id)
                    ->pluck('user_id')
            )
            ->unique()
            ->toArray();

        // users fatch with friendsIds
        $friends = User::whereIn('id', $friendIds)
            ->select('id', 'name', 'username', 'email', 'avatar')
            ->paginate(15);

        return $this->success(
            FriendListResource::collection($friends),
            'Friend list fetched successfully.'
        );
    }

    // list of another user's friend list
    public function userFriendList($userId)
    {
        // Check if user exists
        $profileUser = User::findOrFail($userId);

        // Optional: Check if blocked or privacy settings
        $currentUser = auth('api')->user();


        // friend IDs collect from both side
        $friendIds = DB::table('friends')
            ->where('user_id', $userId)
            ->pluck('friend_id')
            ->merge(
                DB::table('friends')
                    ->where('friend_id', $userId)
                    ->pluck('user_id')
            )
            ->unique()
            ->toArray();

        // users fatch with friendsIds
        $friends = User::whereIn('id', $friendIds)
            ->select('id', 'name', 'email', 'phone', 'avatar')
            ->paginate(15);

        return $this->success(
            FriendListResource::collection($friends),
            $profileUser->name . '\'s friend list fetched successfully.'
        );
    }

    // block user
    public function blockUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = auth('api')->user();
        $targetId = $request->user_id;

        if ($user->id == $targetId) {
            return $this->error([], 'You cannot block yourself.', 400);
        }

        // Check if already blocked
        if ($user->blockedUsers()->where('blocked_user_id', $targetId)->exists()) {
            return $this->error([], 'User is already blocked.', 409);
        }

        try {
            DB::beginTransaction();

            // Block the user
            $user->blockedUsers()->attach($targetId);

            // Unfriend if they are friends (both directions in friends table)
            DB::table('friends')->where(function ($q) use ($user, $targetId) {
                $q->where('user_id', $user->id)->where('friend_id', $targetId);
            })->orWhere(function ($q) use ($user, $targetId) {
                $q->where('user_id', $targetId)->where('friend_id', $user->id);
            })->delete();

            // Delete any pending/accepted friend requests
            DB::table('friend_requests')->where(function ($q) use ($user, $targetId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $targetId);
            })->orWhere(function ($q) use ($user, $targetId) {
                $q->where('sender_id', $targetId)->where('receiver_id', $user->id);
            })->delete();

            DB::commit();

            return $this->success(null, 'User blocked successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Something went wrong: ' . $e->getMessage(), 500);
        }
    }

    // unblock user
    public function unblockUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = auth('api')->user();
        $targetId = $request->user_id;

        // Check if blocked
        if (!$user->blockedUsers()->where('blocked_user_id', $targetId)->exists()) {
            return $this->error([], 'User is not blocked.', 404);
        }

        // Unblock the user
        $user->blockedUsers()->detach($targetId);

        return $this->success(null, 'User unblocked successfully.');
    }

    // blocked users list
    public function blockedList()
    {
        $user = auth('api')->user();

        // Get blocked users
        $blockedUsers = $user->blockedUsers()
            ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.avatar')
            ->paginate(15);

        return $this->success(
            FriendListResource::collection($blockedUsers),
            'Blocked users list fetched successfully.'
        );
    }

    // unfriend user
    public function unfriend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = auth('api')->user();
        $targetId = $request->friend_id;

        try {
            DB::beginTransaction();

            // Unfriend (both directions)
            DB::table('friends')->where(function ($q) use ($user, $targetId) {
                $q->where('user_id', $user->id)->where('friend_id', $targetId);
            })->orWhere(function ($q) use ($user, $targetId) {
                $q->where('user_id', $targetId)->where('friend_id', $user->id);
            })->delete();

            // Delete any existing friend requests
            DB::table('friend_requests')->where(function ($q) use ($user, $targetId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $targetId);
            })->orWhere(function ($q) use ($user, $targetId) {
                $q->where('sender_id', $targetId)->where('receiver_id', $user->id);
            })->delete();

            DB::commit();

            return $this->success(null, 'User unfriended successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error([], 'Something went wrong: ' . $e->getMessage(), 500);
        }
    }
}
