@extends('layouts.master')

@section('title', 'Create Merchant')

@section('content')
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Create Merchant</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('merchant.index') }}">List of Merchants</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Merchant</li>
                        </ol>
                    </div>
                </div>
                <!--end::Row-->
            </div>
            <!--end::Container-->
        </div>
        <div class="app-content">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-lg">
                        <div class="card card-warning card-outline">
                            <div class="card-body">
                                <div class="col-lg-12">
                                    {{-- SHOW LARAVEL ERROR VALIDATIONS --}}
                                    {{-- @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif --}}

                                    <form action="{{ route('merchant.store') }}" method="POST" class="row g-3">
                                        @csrf
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="floatingInput" name="name" value="{{ old('name') }}" placeholder="Merchant Name">
                                                <label for="floatingInput">Merchant Name</label>
                                                @error('name')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="number" class="form-control @error('dtl') is-invalid @enderror" id="floatingInput" name="dtl" value="{{ old('dtl') }}" placeholder="Daily Transaction Limit">
                                                <label for="floatingInput">Daily Transaction Limit</label>
                                                @error('name')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="floatingInput" name="email" value="{{ old('email') }}" placeholder="name@example.com">
                                                <label for="floatingInput">Email</label>
                                                @error('email')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('api_key') is-invalid @enderror" id="floatingInput" name="api_key" value="{{ old('api_key') }}" placeholder="API KEY">
                                                <label for="floatingInput">Public Key</label>
                                                @error('api_key')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('api_secret') is-invalid @enderror" id="floatingInput" name="api_secret" value="{{ old('api_secret') }}" placeholder="API SECRET">
                                                <label for="floatingInput">Secret Key</label>
                                                @error('api_secret')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success px-5">Save</button>
                                            <a href="{{ route('merchant.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>
                <!--end::Row-->
            </div>
            <!--end::Container-->
        </div>
        <!--end::App Content-->
    </main>
@endsection

@section('scripts')
@endsection
