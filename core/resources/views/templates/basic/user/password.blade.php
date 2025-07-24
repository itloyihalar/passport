@extends($activeTemplate . 'layouts.master')

@section('content')
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <div class="card custom--card">
                        <h5 class="title px-3 pt-4">@lang('Change your password')</h5>
                        <p class="px-3">@lang('To ensure the security of your account, please update your password regularly.')</p>
                        <div class="card-body">
                            <form method="post">
                                @csrf
                                <div class="form-group">
                                    <label for="your-password" class="form--label">@lang('Current Password')</label>
                                    <div class="position-relative">
                                        <input id="your-password" type="password" name="current_password"
                                            class="form--control form-control" autocomplete="current-password" required>
                                        <span class="password-show-hide fa-solid fa-eye toggle-password"
                                            id="toggle-password" aria-label="Toggle password visibility"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="your-password" class="form--label">@lang('Password')</label>
                                    <div class="position-relative">
                                        <input id="your-password" type="password" name="password"
                                            class="form--control form-control @if (gs('secure_password')) secure-password @endif"
                                            autocomplete="off" required>
                                        <span class="password-show-hide fa-solid fa-eye toggle-password"
                                            id="toggle-password" aria-label="Toggle password visibility"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="confirm-password" class="form--label">@lang('Confirm Password')</label>
                                    <div class="position-relative">
                                        <input id="confirm-password" name="password_confirmation" type="password"
                                            class="form--control form-control" required>
                                        <span class="password-show-hide fa-solid fa-eye toggle-password"
                                            id="toggle-confirm-password"
                                            aria-label="Toggle confirm password visibility"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif
