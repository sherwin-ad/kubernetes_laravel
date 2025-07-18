@extends('layouts.master')

@section('title', 'Create Aggregator')

@section('content')
    <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Row-->
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Create Aggregator</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('aggregator.index') }}">List of Aggregators</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Aggregator</li>
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

                                    <form action="{{ route('aggregator.store') }}" method="POST" class="row g-3">
                                        @csrf
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="floatingInput" name="name" value="{{ old('name') }}" placeholder="Merchant Name">
                                                <label for="floatingInput">Aggregator Name</label>
                                                @error('name')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('account_name') is-invalid @enderror" id="floatingInput" name="account_name" value="{{ old('account_name') }}" placeholder="Merchant Name">
                                                <label for="floatingInput">Contact Person</label>
                                                @error('account_name')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('bank') is-invalid @enderror" id="floatingInput" name="bank" value="{{ old('bank') }}" placeholder="Merchant Name">
                                                <label for="floatingInput">Bank</label>
                                                @error('bank')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="floatingInput" name="account_number" value="{{ old('account_number') }}" placeholder="Merchant Name">
                                                <label for="floatingInput">Bank Account Number</label>
                                                @error('account_number')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control @error('rate') is-invalid @enderror" id="floatingInput" name="rate" value="{{ old('rate') }}" placeholder="Merchant Name">
                                                <label for="floatingInput">Rate</label>
                                                @error('rate')
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
                                                @error('dtl')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input autocomplete="off" type="text" class="form-control @error('email') is-invalid @enderror" id="floatingInput" name="email" value="{{ old('email') }}" placeholder="Email">
                                                <label for="floatingInput">Email</label>
                                                @error('email')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- PASSWORD --}}
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="floatingInput" name="password" placeholder="Password">
                                                <label for="floatingInput">Password</label>
                                                @error('password')
                                                    <div class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                        </div>


                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success px-5">Save</button>
                                            <a href="{{ route('aggregator.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
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
