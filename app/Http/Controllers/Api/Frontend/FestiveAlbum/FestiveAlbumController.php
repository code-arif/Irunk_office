<?php

namespace App\Http\Controllers\Api\Frontend\FestiveAlbum;

use App\Helpers\Helper;
use App\Models\FestiveAlbum;
use App\Models\FestiveAlbumImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\FestiveAlbumsRequest;
use App\Http\Resources\MyalbumResource;
use App\Models\Festival;

class FestiveAlbumController extends Controller
{
    public function addFestiveAlbum(FestiveAlbumsRequest $request)
    {
        $validated = $request->validated();
        $user = auth('api')->user();

        $album = FestiveAlbum::create([
            'user_id'         => $user->id,
            'favourite_set'   => $validated['favourite_set'],
            'favourite_day'   => $validated['favourite_day'],
            'festive_date'    => $validated['festive_date'],
            'camp_experience' => $validated['camp_experience'],
            'unique_moments'  => $validated['unique_moments'],
            'dairy_entry'     => $validated['dairy_entry'],
            'status'          => $validated['status'],
            'fest_type'       => $validated['fest_type'],
        ]);

        // ✅ File upload MUST use $request, not $validated
        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $file) {

                $path = Helper::fileUpload(
                    $file,
                    'festive_album/images',
                    getFileName($file)
                );

                FestiveAlbumImage::create([
                    'festive_album_id'    => $album->id,
                    'image_or_video_path' => $path,
                ]);
            }
        }

        $album->load('festiveAlbumImages');

        return Helper::jsonResponse(true, 'Festive album added successfully', 200, $album);
    }

    public function updateAlbum(FestiveAlbumsRequest $request, $id)
    {
        $validated = $request->validated();
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $album = FestiveAlbum::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        if (! $album) {
            return Helper::jsonResponse(false, 'Festive album not found.', 404);
        }

        $album->update([
            'favourite_set'   => $validated['favourite_set'],
            'favourite_day'   => $validated['favourite_day'],
            'festive_date'    => $validated['festive_date'],
            'camp_experience' => $validated['camp_experience'],
            'unique_moments'  => $validated['unique_moments'],
            'dairy_entry'     => $validated['dairy_entry'],
            'status'          => $validated['status'],
            'fest_type'       => $validated['fest_type'],
        ]);

        // ✅ File upload MUST use $request, not $validated
        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $file) {

                $path = Helper::fileUpload(
                    $file,
                    'festive_album/images',
                    getFileName($file)
                );

                FestiveAlbumImage::create([
                    'festive_album_id'    => $album->id,
                    'image_or_video_path' => $path,
                ]);
            }
        }

        $album->load('festiveAlbumImages');

        return Helper::jsonResponse(true, 'Festive album updated successfully', 200, $album);
    }

    // get my albums
    public function myAlbums()
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $albums = FestiveAlbum::where('user_id', $user->id)
            ->with('festiveAlbumImages') // make sure relation is correct
            ->get();

        if ($albums->isEmpty()) {
            return Helper::jsonResponse(false, 'No albums found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'My Festive Albums',
            'data'    => MyalbumResource::collection($albums), // ✅ collection fix
        ]);
    }

    // Get Festive
    public function getFestive()
    {
        $festives = Festival::where('status', 'active')->get();

        if ($festives->isEmpty()) {
            return Helper::jsonResponse(false, 'No festive types found.', 404);
        }

        $festives = $festives->map(function ($festive) {
            return [
                'id'            => $festive->id,
                'festival_name' => $festive->festival_name,
                'image'         => $festive->image ? url($festive->image) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Festive Types',
            'data'    => $festives,
        ]);
    }
    // Public albums

    public function getPublicAlbums()
    {
        $albums = FestiveAlbum::where('status', 'public')
            ->with('festiveAlbumImages') // make sure relation is correct
            ->get();

        if ($albums->isEmpty()) {
            return Helper::jsonResponse(false, 'No public albums found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Public Festive Albums',
            'data'    => MyalbumResource::collection($albums), // ✅ collection fix
        ]);
    }

    // Get private albums
    public function getPrivateAlbums()
    {
        $albums = FestiveAlbum::where('status', 'private')
            ->with('festiveAlbumImages') // make sure relation is correct
            ->get();

        if ($albums->isEmpty()) {
            return Helper::jsonResponse(false, 'No private albums found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Private Festive Albums',
            'data'    => MyalbumResource::collection($albums), // ✅ collection fix
        ]);
    }
    // Album Details

    public function albumDetails($id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $album = FestiveAlbum::where('id', $id)
            ->where('user_id', $user->id)
            ->with('festiveAlbumImages') // make sure relation is correct
            ->first();

        if (! $album) {
            return Helper::jsonResponse(false, 'Album not found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Festive Album Details',
            'data'    => new MyalbumResource($album), // ✅ single resource fix
        ]);
    }

    // delete album image or video
    public function albumImageOrVideoDelete($id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        // Find the image/video and make sure it belongs to user's album
        $image = FestiveAlbumImage::where('id', $id)
            ->whereHas('festiveAlbum', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (! $image) {
            return Helper::jsonResponse(false, 'Image/Video not found', 404);
        }

        // Delete the file from storage if it exists
        if (!empty($image->getRawOriginal('image_or_video_path'))) {
            Helper::fileDelete(public_path($image->getRawOriginal('image_or_video_path')));
        }

        // Delete the database record
        $image->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Image/Video deleted successfully',
        ]);
    }

    // Delete album along with its images/videos
    public function destroy($id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $album = FestiveAlbum::where('id', $id)->where('user_id', $user->id)->first();;
        if (! $album) {
            return Helper::jsonResponse(false, 'Festive album not found.', 404);
        }

        // Delete associated images/videos from storage
        foreach ($album->festiveAlbumImages as $image) {
            if (!empty($image->getRawOriginal('image_or_video_path'))) {
                Helper::fileDelete(public_path($image->getRawOriginal('image_or_video_path')));
            }
        }

        // Delete the album
        $album->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Festive album deleted successfully',
        ]);
    }

    
}
