<?php

namespace App\Http\Controllers\Web\Backend;

use Exception;
use App\Models\Artist;
use App\Helpers\Helper;
use App\Models\Festival;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class FestivalController extends Controller
{
   public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Festival::query()->orderBy('id', 'desc')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    if ($data->image) {
                        $url = asset($data->image);
                        return '<img src="' . $url . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    } else {
                        return '<img src="' . asset('default/logo.svg') . '" alt="image" width="50px" height="50px" style="margin-left:20px;">';
                    }
                }) 
                ->addColumn('status', function ($data) {
                    $backgroundColor = $data->status == "active" ? '#4CAF50' : '#ccc';
                    $sliderTranslateX = $data->status == "active" ? '26px' : '2px';
                    $sliderStyles = "position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background-color: white; border-radius: 50%; transition: transform 0.3s ease; transform: translateX($sliderTranslateX);";

                    $status = '<div class="form-check form-switch" style="margin-left:40px; position: relative; width: 50px; height: 24px; background-color: ' . $backgroundColor . '; border-radius: 12px; transition: background-color 0.3s ease; cursor: pointer;">';
                    $status .= '<input onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" class="form-check-input" id="customSwitch' . $data->id . '" getAreaid="' . $data->id . '" name="status" style="position: absolute; width: 100%; height: 100%; opacity: 0; z-index: 2; cursor: pointer;">';
                    $status .= '<span style="' . $sliderStyles . '"></span>';
                    $status .= '<label for="customSwitch' . $data->id . '" class="form-check-label" style="margin-left: 10px;"></label>';
                    $status .= '</div>';

                    return $status;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">

                                <a href="#" type="button" onclick="goToEdit(' . $data->id . ')" class="btn btn-primary fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-edit"></i>
                                </a>

                                <a href="#" type="button" onclick="goToOpen(' . $data->id . ')" class="btn btn-success fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-eye"></i>
                                </a>

                                <a href="#" type="button" onclick="showDeleteConfirm(' . $data->id . ')" class="btn btn-danger fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-trash"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['status', 'action', 'image'])
                ->make();
        }
        return view("backend.layouts.festival.index");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.layouts.festival.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'festival_name'             => 'required|max:250',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();

            $festival = new Festival();

            if ($request->hasFile('image')) {
                $data['image'] = Helper::fileUpload($request->file('image'), 'festival', time() . '_' . getFileName($request->file('image')));
            }

            $festival->festival_name = $data['festival_name'];
            $festival->image = $data['image'] ?? null;
            $festival->save();

            session()->put('t-success', 'festival created successfully');
        } catch (Exception $e) {

            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.festival.index')->with('t-success', 'festival created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(festival $festival, $id)
    {
        $festival = Festival::where('id', $id)->first();
        return view('backend.layouts.festival.show', compact('festival'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $festival = Festival::findOrFail($id);
        return view('backend.layouts.festival.edit', compact('festival'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'festival_name'             => 'required|max:250',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();

            $festival = Festival::findOrFail($id);

            if ($request->hasFile('image')) {
                $data['image'] = Helper::fileUpload($request->file('image'), 'festival', time() . '_' . getFileName($request->file('image')));
            }

            $festival->festival_name = $data['festival_name'];
            $festival->image = $data['image'] ?? $festival->image;        
            $festival->save();

            session()->put('t-success', 'festival updated successfully');
        } catch (Exception $e) {

            session()->put('t-error', $e->getMessage());
        }

        return redirect()->route('admin.festival.edit', $festival->id)->with('t-success', 'festival updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $data = Festival::findOrFail($id);

            if ($data->image && file_exists(public_path($data->image))) {
                Helper::fileDelete(public_path($data->image));
            }

            $data->delete();
            return response()->json([
                'status' => 't-success',
                'message' => 'Your action was successful!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Your action was successful!'
            ]);
        }
    }

    public function status(int $id): JsonResponse
    {
        $data = Festival::findOrFail($id);
        if (!$data) {
            return response()->json([
                'status' => 't-error',
                'message' => 'Item not found.',
            ]);
        }
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();
        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!',
        ]);
    }
}
