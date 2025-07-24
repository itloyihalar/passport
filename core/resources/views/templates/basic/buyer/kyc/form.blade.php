@extends($activeTemplate.'layouts.buyer_master')
@section('content')
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card custom--card">
                    <div class="card-body">
                        <form action="{{route('buyer.kyc.submit')}}" method="post" enctype="multipart/form-data">
                            @csrf
                            <x-viser-form identifier="act" identifierValue="kyc_buyer" />
                            <div class="form-group">
                                <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@endsection


@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush

@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush
