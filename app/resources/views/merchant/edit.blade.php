@extends('layouts.master')

@section('title', 'Edit Merchant')

@section('content')
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Edit Merchant</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('merchant.index') }}">List of Merchants</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Merchant</li>
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
                                    <form action="{{ route('merchant.update', $merchant->id) }}" method="POST" class="row g-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="floatingInput" name="name" value="{{ old('name', $merchant->name) }}" placeholder="Merchant Name">
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
                                                <input type="number" class="form-control @error('dtl') is-invalid @enderror" id="floatingInput" name="dtl" value="{{ old('name', $merchant->dtl) }}" placeholder="Daily Transaction Limit">
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
                                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="floatingInput" name="email" value="{{ old('email', $merchant->email) }}" placeholder="name@example.com">
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
                                                <input type="text" class="form-control @error('api_key') is-invalid @enderror" id="floatingInput" name="api_key" value="{{ old('api_key', $merchant->api_key) }}" placeholder="API KEY">
                                                <label for="floatingInput">API KEY</label>
                                                @error('api_key')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('api_secret') is-invalid @enderror" id="floatingInput" name="api_secret" value="{{ old('api_secret', $merchant->api_secret) }}" placeholder="API SECRET">
                                                <label for="floatingInput">API SECRET</label>
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
