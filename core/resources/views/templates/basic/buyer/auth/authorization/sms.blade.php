@extends($activeTemplate . 'layouts.app')
@section('panel')
    <div class="user-data-section">
        <div class="container">
            <div class="user-data-section__content">
                <div class="container">
                    <div class="d-flex justify-content-center">
                        <div class="verification-code-wrapper">
                            <div class="verification-area">
                                <div class="proposal-top-content">
                                    <a href="{{ route('home') }}" class="user-data-header__logo">
                                        <img src="{{ siteLogo() }}" alt="">
                                    </a>
                                    <h5 class="pb-3 text-center border-bottom">@lang('Verify Mobile Number')</h5>
                                </div>
                                <form action="{{ route('buyer.verify.mobile') }}" method="POST" class="submit-form">
                                    @csrf
                                    <p class="verification-text">@lang('A 6 digit verification code sent to your mobile number') :
                                        +{{ showMobileNumber(auth()->guard('buyer')->user()->mobileNumber) }}</p>
                                    @include($activeTemplate . 'partials.verification_code')
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                                    </div>
                                    <div class="form-group">
                                        <p>
                                            @lang('If you don\'t get any code'), <span class="countdown-wrapper">@lang('try again after') <span
                                                    id="countdown" class="fw-bold">--</span> @lang('seconds')</span> <a
                                                href="{{ route('buyer.send.verify.code', 'sms') }}"
                                                class="try-again-link d-none"> @lang('Try again')</a>
                                        </p>
                                        <a class="btn btn-outline--danger mt-3 btn--sm" href="{{ route('buyer.logout') }}">@lang('Logout')</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        var distance = Number("{{ @$user->ver_code_send_at->addMinutes(2)->timestamp - time() }}");
        var x = setInterval(function() {
            distance--;
            document.getElementById("countdown").innerHTML = distance;
            if (distance <= 0) {
                clearInterval(x);
                document.querySelector('.countdown-wrapper').classList.add('d-none');
                document.querySelector('.try-again-link').classList.remove('d-none');
            }
        }, 1000);
    </script>
@endpush
