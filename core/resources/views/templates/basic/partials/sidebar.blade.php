@php
    $user = auth()->user();
@endphp
<div class="sidebar-menu flex-between">
    <div class="sidebar-menu__inner">
        <span class="sidebar-menu__close d-lg-none d-block"><i class="fas fa-times"></i></span>
        <!-- Sidebar Logo Start -->
        <div class="sidebar-logo">
            <a href="{{ route('home') }}" class="sidebar-logo__link"><img src="{{ siteLogo('dark') }}" alt=""></a>
        </div>
        <!-- Sidebar Logo End -->
        <div class="sidebar-menu__top">
            <div class="shape">
                <img src="{{ asset($activeTemplateTrue . 'shape/d-shape.png') }}" alt="">
            </div>
            <span class="icon">
                <i class="las la-wallet"></i>
            </span>
            <div class="content">
                <span class="title">@lang('Balance')</span>
                <h6 class="number">{{ showAmount(@$user->balance) }}</h6>
            </div>
         </div>
        <!-- ========= Sidebar Menu Start ================ -->
        <ul class="sidebar-menu-list">
            <li class="sidebar-menu-list__item {{ menuActive('user.home') }}">
                <a href="{{ route('user.home') }}" class="sidebar-menu-list__link">
                    <span class="icon"> <i class="las la-home"></i> </span>
                    <span class="text">@lang('Dashboard') </span>
                </a>
            </li>
            <li class="sidebar-menu-list__item {{ menuActive('user.bid.index') }}">
                <a href="{{ route('user.bid.index') }}" class="sidebar-menu-list__link">
                    <span class="icon"><i class="las la-gavel"></i></span>
                    <span class="text">@lang('All Bids') </span>
                </a>
            </li>
            <li
                class="sidebar-menu-list__item {{ menuActive(['user.project.index', 'user.project.form', 'user.project.detail']) }}">
                <a href="{{ route('user.project.index') }}" class="sidebar-menu-list__link">
                    <span class="icon"><i class="las la-briefcase"></i></span>
                    <span class="text">@lang('My Projects') </span>
                </a>
            </li>
            <li
                class="sidebar-menu-list__item {{ menuActive(['user.withdraw', 'user.withdraw.history']) }} has-dropdown">
                <a href="javascript:void(0)" class="sidebar-menu-list__link">
                    <span class="icon"><i class="las la-money-check-alt"></i></span>
                    <span class="text"> @lang('Withdraw') </span>
                </a>
                <div class="sidebar-submenu">
                    <ul class="sidebar-submenu-list">
                        <li class="sidebar-submenu-list__item {{ menuActive('user.withdraw') }}">
                            <a href="{{ route('user.withdraw') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Withdraw Money') </span>
                            </a>
                        </li>
                        <li class="sidebar-submenu-list__item {{ menuActive('user.withdraw.history') }}">
                            <a href="{{ route('user.withdraw.history') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Withdraw Log') </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="sidebar-menu-list__item {{ menuActive('user.transactions') }}">
                <a href="{{ route('user.transactions') }}" class="sidebar-menu-list__link">
                    <span class="icon"><i class="las la-exchange-alt"></i> </span>
                    <span class="text">@lang('Transactions') </span>
                </a>
            </li>
            <li
                class="sidebar-menu-list__item {{ menuActive(['ticket.open', 'ticket.index', 'ticket.view']) }} has-dropdown">
                <a href="javascript:void(0)" class="sidebar-menu-list__link">
                    <span class="icon"><i class="las la-ticket-alt"></i></span>
                    <span class="text"> @lang('Support Ticket') </span>
                </a>
                <div class="sidebar-submenu">
                    <ul class="sidebar-submenu-list">
                        <li class="sidebar-submenu-list__item {{ menuActive('ticket.open') }}">
                            <a href="{{ route('ticket.open') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Create New')</span>
                            </a>
                        </li>
                        <li class="sidebar-submenu-list__item {{ menuActive('ticket.index') }}">
                            <a href="{{ route('ticket.index') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Ticket History') </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="sidebar-menu-list__item {{ menuActive('user.conversation.*') }}">
                <a href="{{ route('user.conversation.index') }}" class="sidebar-menu-list__link">
                    <span class="icon"><i class="lab la-rocketchat"></i></span>
                    <span class="text"> @lang('Chat')</span>
                </a>
            </li>

            <li
                class="sidebar-menu-list__item {{ menuActive(['user.profile.setting', 'user.profile.skill', 'user.profile.education', 'user.profile.portfolio', 'user.change.password', 'user.twofactor']) }} has-dropdown">
                <a href="javascript:void(0)" class="sidebar-menu-list__link">
                    <span class="icon"><i class="las la-cog"></i></span>
                    <span class="text"> @lang('Settings') </span>
                </a>
                <div class="sidebar-submenu">
                    <ul class="sidebar-submenu-list">
                        <li class="sidebar-submenu-list__item {{ menuActive('user.profile.setting') }}">
                            <a href="{{ route('user.profile.setting') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Profile Setting')</span>
                            </a>
                        </li>
                        <li class="sidebar-submenu-list__item {{ menuActive('user.change.password') }}">
                            <a href="{{ route('user.change.password') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('Change Password')</span>
                            </a>
                        </li>
                        <li class="sidebar-submenu-list__item {{ menuActive('user.twofactor') }}">
                            <a href="{{ route('user.twofactor') }}" class="sidebar-submenu-list__link">
                                <span class="text">@lang('2FA Security') </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="sidebar-menu-list__item">
                <a href="{{ route('user.logout') }}" class="sidebar-menu-list__link">
                    <span class="icon"><i class="las la-sign-out-alt"></i></span>
                    <span class="text">@lang('Logout')</span>
                </a>
            </li>
        </ul>
        <!-- ========= Sidebar Menu End ================ -->
    </div>
</div>
