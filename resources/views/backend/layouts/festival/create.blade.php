@extends('backend.app', ['title' => 'Create Festival'])

@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Festival</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Festival</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">
                    <div class="card post-sales-main">
                        <div class="card-header border-bottom">
                            <h3 class="card-title mb-0">Create Festival</h3>
                            <div class="card-options">
                                <a href="javascript:window.history.back()" class="btn btn-sm btn-primary">Back</a>
                            </div>
                        </div>
                        <div class="card-body border-0">
                            <form class="form form-horizontal" method="POST" action="{{ route('admin.festival.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row mb-4">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="festival_name" class="form-label">Festival Name:</label>
                                                <input type="text" class="form-control @error('festival_name') is-invalid @enderror" name="festival_name" placeholder="Enter here festival name" id="festival_name" value="{{ old('festival_name') ?? '' }}">
                                                @error('festival_name')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>                               

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="thumbnail" class="form-label">Image:</label>
                                                <input type="file" class="dropify form-control @error('image') is-invalid @enderror" data-default-file="{{ url('default/logo.png') }}" name="image" id="image">
                                                <p class="textTransform">Image Size Less than 5MB and Image Type must be jpeg,jpg,png.</p>
                                                @error('image')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button class="submit btn btn-primary" type="submit">Submit</button>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection
@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#category_id').select2();
    });
</script>
@endpush