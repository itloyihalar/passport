@extends('Template::layouts.app')
@section('panel')
    <div class="dashboard position-relative">
        <div class="dashboard__inner flex-wrap">
            <div class="dashboard__left">
                <!-- ====================== Sidebar menu Start ========================= -->
                @include('Template::partials.buyer_sidebar')
            </div>
            <div class="dashboard__right">
                <div class="dashboard-header">
                    <div class="dashboard-header__inner flex-between">
                        <div class="dashboard-header__left">
                            <div class="dashboard-body__bar d-lg-none d-inline-block">
                                <span class="dashboard-body__bar-icon"><i class="fas fa-bars"></i></span>
                            </div>
                            <h6 class="title"> {{ __(@$pageTitle) }} </h6>
                        </div>
                        <div class="user-info">
                            <div class="user-info__right">
                                <div class="notification">
                                    <a class="notification-link" href="{{ route('buyer.conversation.index') }}"><i
                                            class="las la-envelope"></i>
                                        @if ($unreadCount > 0)
                                            <span class="notification-number">{{ $unreadCount }}</span>
                                        @endif
                                    </a>
                                </div>
                                <div class="user-info__button">
                                    <div class="user-info__thumb">
                                        <img src="{{ getImage(getFilePath('buyerProfile') . '/' . auth()->guard('buyer')->user()->image, avatar: true) }}"
                                            alt="">
                                    </div>
                                </div>
                            </div>
                            <ul class="user-info-dropdown">
                                <div class="profile-icon mb-2">
                                    <div class="name">{{ strLimit(auth()->guard('buyer')->user()->fullname) }}</div>
                                    <div class="role">@lang('Buyer')</div>
                                </div>
                                <li class="user-info-dropdown__item"><a class="user-info-dropdown__link"
                                        href="{{ route('buyer.profile.setting') }}">
                                        <span class="icon"><i class="fas fa-user-circle"></i></span>
                                        <span class="text">@lang('My Profile')</span>
                                    </a></li>
                                <li class="user-info-dropdown__item"><a class="user-info-dropdown__link"
                                        href="{{ route('buyer.change.password') }}">
                                        <span class="icon"><i class="fas fa-lock"></i></span>
                                        <span class="text">@lang('Password')</span>
                                    </a></li>
                                <li class="user-info-dropdown__item"><a class="user-info-dropdown__link"
                                        href="{{ route('buyer.twofactor') }}">
                                        <span class="icon"><i class="fas fa-key"></i></span>
                                        <span class="text">@lang('2FA Security')</span>
                                    </a></li>
                                <li class="user-info-dropdown__item"><a class="user-info-dropdown__link"
                                        href="{{ route('buyer.logout') }}">
                                        <span class="icon"><i class="fas fa-cog"></i></span>
                                        <span class="text">@lang('Logout')</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="dashboard-body">

                    @yield('content')
                </div>
            </div>
        </div>
    </div>
@endsection
