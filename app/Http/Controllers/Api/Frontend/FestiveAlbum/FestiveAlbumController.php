<?php

namespace App\Http\Controllers\Api\Frontend\FestiveAlbum;

use App\Models\Artist;
use App\Models\Festival;
use Illuminate\Http\Request;
use App\Models\FestiveDocument;
use App\Models\FestiveExperience;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\MyalbumResource;
use App\Http\Resources\AllAlbumResource;
use App\Http\Requests\FestiveAlbumsRequest;
use App\Http\Requests\FestiveUpdateAlbumsRequest;

class FestiveAlbumController extends Controller
{
    /**
     * Create a new festive album
     */
    public function store(FestiveAlbumsRequest $request)
    {
        $user_id = auth()->guard('api')->id();

        if (! $user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login.'
            ], 401);
        }

        // Validate date requirement based on fest_type
        if ($request->day_type == 'single-day') {
            if (!$request->festive_date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Festive date is required for single-day festivals.'
                ], 422);
            }
        } else {
            $request->merge([
                'festive_date' => null
            ]);
        }
        // 1. Find Festival by Name
        $festival = Festival::where('festival_name', 'LIKE', $request->festival_name)->first();

        if (!$festival) {
            return response()->json([
                'success' => false,
                'message' => 'Festival not found with this name.'
            ], 404);
        }

        $festival_id = $festival->id;

        if ($request->hasFile('image')) {
            $artistImageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/artists/images'), $artistImageName);
        } else {
            $imagePath = null;
        }

        $artist = Artist::create([
            'user_id' => $user_id,
            'festival_id' => $festival_id,
            'name' => $request->name,
            'image' => 'uploads/artists/images/' . $artistImageName,
        ]);

        $experience = FestiveExperience::create([
            'artist_id' => $artist->id,
            'favourite_set' => $request->favourite_set,
            'favourite_day' => $request->favourite_day,
            'camp_experience' => $request->camp_experience,
            'festive_story' => $request->festive_story,
            'festive_date' => $request->festive_date,
            'locations' => $request->locations ?? null,
            'day_type' => $request->day_type ?? 'none',
            'status' => $request->status ?? 'public',
            'fest_type' => $request->fest_type ?? 'previous',
        ]);

        if ($request->hasFile('documents')) {

            foreach ($request->file('documents') as $file) {

                $extension = strtolower($file->getClientOriginalExtension());

                if (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])) {
                    $folder = 'uploads/festive/videos/';
                } else {
                    $folder = 'uploads/festive/images/';
                }

                // Create unique filename
                $fileName = uniqid() . '.' . $extension;
                $file->move(public_path($folder), $fileName);
                FestiveDocument::create([
                    'artist_id' => $artist->id,
                    'video_image' => $folder . $fileName,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Festive album created successfully',
            'artist' => $artist,
            'experience' => $experience,
            'festival_id' => $festival_id
        ], 201);
    }

    /**
     * Update a festive album
     */
    public function update(FestiveUpdateAlbumsRequest $request, $artist_id)
    {
        $user_id = auth()->guard('api')->id();

        if (! $user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login.'
            ], 401);
        }

        // Find artist
        $artist = Artist::where('id', $artist_id)
            ->where('user_id', $user_id)
            ->first();

        if (! $artist) {
            return response()->json([
                'success' => false,
                'code'    => 404,
                'message' => 'Artist not found or unauthorized.'
            ]);
        }

        // Update Festival by Name (if changed)
        if ($request->festival_name) {
            $festival = Festival::where('festival_name', $request->festival_name)->first();

            if (!$festival) {
                return response()->json([
                    'success' => false,
                    'message' => 'Festival not found.'
                ], 404);
            }

            $artist->festival_id = $festival->id;
        }

        // Update Artist Image
        if ($request->hasFile('image')) {

            // delete previous image
            if (file_exists(public_path($artist->image))) {
                unlink(public_path($artist->image));
            }

            $artistImageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/artists/images'), $artistImageName);

            $artist->image = 'uploads/artists/images/' . $artistImageName;
        }

        // ✅ Update artist name
        $artist->name = $request->name ?? $artist->name;
        $artist->save();

        // ✅ Update festive experience
        $experience = FestiveExperience::where('artist_id', $artist->id)->first();

        if ($experience) {
            $experience->update([
                'favourite_set'   => $request->favourite_set ?? $experience->favourite_set,
                'favourite_day'   => $request->favourite_day ?? $experience->favourite_day,
                'day_type'        => $request->day_type ?? $experience->day_type,
                'camp_experience' => $request->camp_experience ?? $experience->camp_experience,
                'festive_story'   => $request->festive_story ?? $experience->festive_story,
                'festive_date'    => $request->festive_date ?? $experience->festive_date,
                'locations'      => $request->locations ?? $experience->locations,
                'status'          => $request->status ?? $experience->status,
                'fest_type'       => $request->fest_type ?? $experience->fest_type,
            ]);
        }

        // ✅ Add New Documents (images/videos)
        if ($request->hasFile('documents')) {

            foreach ($request->file('documents') as $file) {

                $extension = strtolower($file->getClientOriginalExtension());

                if (in_array($extension, ['mp4', 'mov', 'avi', 'mkv'])) {
                    $folder = 'uploads/festive/videos/';
                } else {
                    $folder = 'uploads/festive/images/';
                }

                $fileName = uniqid() . '.' . $extension;
                $file->move(public_path($folder), $fileName);

                FestiveDocument::create([
                    'artist_id' => $artist->id,
                    'video_image' => $folder . $fileName,
                ]);
            }
        }


        if ($request->delete_document_ids) {
            foreach ($request->delete_document_ids as $docId) {
                $doc = FestiveDocument::find($docId);
                if ($doc && file_exists(public_path($doc->video_image))) {
                    unlink(public_path($doc->video_image));
                }
                FestiveDocument::where('id', $docId)->delete();
            }
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Festive Album updated successfully',

        ]);
    }

    /**
     * Get all albums
     */
    public function allAlbums()
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $albums = Artist::with(['festival', 'experiences', 'documents'])->where('user_id', '!=', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($albums->isEmpty()) {
            return Helper::jsonResponse(false, 'No albums found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'All Festive Albums',
            'data'    => AllAlbumResource::collection($albums), // ✅ collection fix
        ]);
    }

    /**
     * Get my albums
     */
    public function myAlbums(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $type = $request->input('type'); // 1=public, 2=private
        $status = null;

        if ($type == 1) {
            $status = 'public';
        } elseif ($type == 2) {
            $status = 'private';
        }

        $albumsQuery = Artist::with(['festival', 'documents', 'experiences'])
            ->where('user_id', $user->id);

        // Filter albums by experience status
        if ($status) {
            $albumsQuery->whereHas('experiences', function ($query) use ($status) {
                $query->where('status', $status);
            });
        }

        $albums = $albumsQuery->orderBy('created_at', 'desc')->get();

        if ($albums->isEmpty()) {
            return Helper::jsonResponse(false, 'No albums found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'My Festive Albums',
            'data'    => AllAlbumResource::collection($albums),
        ]);
    }

    /**
     * Get festive
     */
    public function getFestive(Request $request)
    {
        $query = Festival::where('status', 'active');

        // Search by festival name
        if ($request->filled('search')) {
            $query->where('festival_name', 'LIKE', '%' . $request->search . '%');
        }

        $festives = $query->get();

        if ($festives->isEmpty()) {
            return Helper::jsonResponse(false, 'No festive found.', 404);
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

    /**
     * Get public albums
     */
    public function getPublicAlbums($status = 'public')
    {
        $user = auth('api')->user();
        if (!$user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        // Only fetch albums that have experiences with the requested status
        $albums = Artist::with([
            'festival',
            'documents',
            'experiences' => function ($query) use ($status) {
                $query->where('status', $status); // only experiences with requested status
            }
        ])
            ->where('user_id', $user->id)
            ->whereHas('experiences', function ($query) use ($status) {
                $query->where('status', $status); // ensures album has at least one experience with this status
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($albums->isEmpty()) {
            return Helper::jsonResponse(false, 'No albums found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => ucfirst($status) . ' Albums',
            'data'    => AllAlbumResource::collection($albums),
        ]);
    }

    /**
     * Get private albums
     */
    public function getPrivateAlbums($status = 'private')
    {
        $user = auth('api')->user();
        if (!$user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }
        $albums = Artist::with([
            'festival',
            'documents',
            'experiences' => function ($query) use ($status) {
                $query->where('status', $status);
            }
        ])
            ->where('user_id', $user->id)
            ->whereHas('experiences', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($albums->isEmpty()) {
            return Helper::jsonResponse(false, 'No albums found.', 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => ucfirst($status) . ' Albums',
            'data'    => AllAlbumResource::collection($albums),
        ]);
    }

    /**
     * Get album details
     */
    public function albumDetails($id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $album = Artist::where('id', $id)
            ->with(['festival', 'experiences', 'documents', 'review.user', 'review.comments', 'review.likes', 'review.comments.replies'])
            ->first();

        // dd($album);
        // $album = Artist::where('id', $id)
        //     ->with([
        //         'festival',
        //         'experiences',
        //         'documents',

        //         'review.user',
        //         'review.likes',

        //         'review.comments.user',
        //         'review.comments.likes',

        //         // Load replies recursively
        //         'review.comments.replies.user',
        //         'review.comments.replies.likes',
        //         'review.comments.replies.replies', // recursive load
        //         'review.comments.replies.replies.user',
        //         'review.comments.replies.replies.likes'
        //     ])
        //     ->first();


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

    /**
     * Delete album image or video
     */
    public function deleteDocuments($id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }

        $image = FestiveDocument::where('id', $id)
            ->whereHas('artist', function ($query) use ($user) {
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
            'message' => 'Image or Video deleted successfully',
        ]);
    }

    /**
     * Delete album along with its images/videos
     */
    public function destroy($id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return Helper::jsonResponse(false, 'Unauthorized. Please login.', 401);
        }
        $album = FestiveExperience::with('documents')->find($id);

        if (!$album) {
            return Helper::jsonResponse(false, 'Festive album not found.', 404);
        }

        if ($album->documents && $album->documents->isNotEmpty()) {
            foreach ($album->documents as $document) {
                if (!empty($document->video_image) && file_exists(public_path($document->video_image))) {
                    unlink(public_path($document->video_image));
                }
                $document->delete();
            }
        }
        $album->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Festive album and associated documents deleted successfully',
        ]);
    }
}
