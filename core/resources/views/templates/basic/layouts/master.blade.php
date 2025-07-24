@extends($activeTemplate . 'layouts.app')
@section('panel')
    <div class="dashboard position-relative">
        <div class="dashboard__inner flex-wrap">
            <!-- ====================== Sidebar menu Start ========================= -->
            @include('Template::partials.sidebar')
            <!-- ====================== Sidebar menu End ========================= -->
            @php
                $user = App\Models\User::with('badge')->find(auth()->id());
            @endphp

            <div class="dashboard__right">
                <!-- Dashboard Header Start -->
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
                                    <a class="notification-link" href="{{ route('user.conversation.index') }}"><i
                                            class="las la-envelope"></i>
                                        @if ($unreadCount > 0)
                                            <span class="notification-number">{{ $unreadCount }}</span>
                                        @endif
                                    </a>
                                </div>
                                @if ($user->badge)
                                    <div class="user-info__button">
                                        <div class="user-info__thumb">
                                            <div class="user-info__button">
                                                <div class="user-info__thumb">
                                                    <div class="profile-badge">
                                                        <img data-bs-toggle="tooltip" title="{{ $user->badge->badge_name }}"
                                                            src="{{ getImage(getFilePath('badge') . '/' . $user->badge->image, getFileSize('badge')) }}"
                                                            alt="">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="user-info__button">
                                    <div class="user-info__thumb">
                                        <img src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, avatar: true) }}"
                                            alt="">
                                    </div>
                                </div>
                            </div>
                            <ul class="user-info-dropdown">
                                <div class="profile-icon mb-2">
                                    <div class="name">{{ strLimit($user->fullname) }}</div>
                                    <div class="role">@lang('Freelancer')</div>
                                </div>
                                <li class="user-info-dropdown__item">
                                    <a class="user-info-dropdown__link" href="{{ route('user.profile.setting') }}">
                                        <span class="icon"><i class="fas fa-user-circle"></i></span>
                                        <span class="text">@lang('My Profile')</span>
                                    </a>
                                </li>
                                <li class="user-info-dropdown__item">
                                    <a class="user-info-dropdown__link" href="{{ route('user.change.password') }}">
                                        <span class="icon"><i class="fas fa-lock"></i></span>
                                        <span class="text">@lang('Password')</span>
                                    </a>
                                </li>
                                <li class="user-info-dropdown__item">
                                    <a class="user-info-dropdown__link" href="{{ route('user.twofactor') }}">
                                        <span class="icon"><i class="fas fa-key"></i></span>
                                        <span class="text">@lang('2FA Security')</span>
                                    </a>
                                </li>
                                <li class="user-info-dropdown__item">
                                    <a class="user-info-dropdown__link" href="{{ route('user.logout') }}">
                                        <span class="icon"><i class="fas fa-cog"></i></span>
                                        <span class="text">@lang('Logout')</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- Dashboard Header End -->

                <!-- Dashboard Body End -->
                <div class="dashboard-body">


                    @yield('content')

                </div>
                <!-- Dashboard Body End -->
            </div>
        </div>
    </div>
@endsection
