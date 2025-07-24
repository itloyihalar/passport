@php
    $text = isset($register) ? 'Register' : 'Login';
@endphp
@if (
    @gs('socialite_credentials')->linkedin->status ||
        @gs('socialite_credentials')->facebook->status == Status::ENABLE ||
        @gs('socialite_credentials')->google->status == Status::ENABLE)
    <div class="social-login-wrapper">
@endif
<ul class="social-login-list">
    @if (@gs('socialite_credentials')->facebook->status == Status::ENABLE)
        <li class="social-login-list__item flex-grow-1">
            <a href="{{ route('user.social.login', 'facebook') }}" class="w-100 social-login-btn">
                <span class="social-login-btn__icon">
                    <img src="{{ asset($activeTemplateTrue . 'images/facebook.svg') }}" alt="">
                </span>
                @lang('Facebook')
            </a>
        </li>
    @endif
    @if (@gs('socialite_credentials')->google->status == Status::ENABLE)
        <li class="social-login-list__item flex-grow-1">
            <a href="{{ route('user.social.login', 'google') }}" class="w-100 social-login-btn">
                <span class="social-login-btn__icon">
                    <img src="{{ asset($activeTemplateTrue . 'images/google.svg') }}" alt="">
                </span>
                @lang('Google')
            </a>
        </li>
    @endif
    @if (@gs('socialite_credentials')->linkedin->status == Status::ENABLE)
        <li class="social-login-list__item flex-grow-1">
            <a href="{{ route('user.social.login', 'linkedin') }}" class="w-100 social-login-btn">
                <span class="social-login-btn__icon">
                    <img src="{{ asset($activeTemplateTrue . 'images/linkdin.svg') }}" alt="">
                </span>
                @lang('Linkedin')
            </a>
        </li>
    @endif
</ul>
@if (
    @gs('socialite_credentials')->linkedin->status ||
        @gs('socialite_credentials')->facebook->status == Status::ENABLE ||
        @gs('socialite_credentials')->google->status == Status::ENABLE)
    <div class="another-login">
        <span class="text"> @lang('or') </span>
    </div>
    </div>
@endif
