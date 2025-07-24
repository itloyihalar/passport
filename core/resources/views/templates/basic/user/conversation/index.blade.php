@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="conversation-container">
        <div class="conversation-sidebar-overlay"></div>
        <div class="container-fluid px-0">
            <div class="chatboard-chat-area">
                <div class="row gy-3 flex-wrap-reverse">
                    <div class="col-xl-4 col-lg-12 col-md-5">
                        <div class="chatboard-chat-left">
                            <div class="chatboard-chat-left__title justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none">
                                        <path
                                            d="M14 9C14 9.53043 13.7893 10.0391 13.4142 10.4142C13.0391 10.7893 12.5304 11 12 11H6L2 15V4C2 2.9 2.9 2 4 2H12C12.5304 2 13.0391 2.21071 13.4142 2.58579C13.7893 2.96086 14 3.46957 14 4V9Z"
                                            stroke="#5B6671" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round">
                                        </path>
                                        <path
                                            d="M18 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V22L18 18H12C11.4696 18 10.9609 17.7893 10.5858 17.4142C10.2107 17.0391 10 16.5304 10 16V15"
                                            stroke="#5B6671" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round">
                                        </path>
                                    </svg>
                                    <span>
                                        @lang('Messages')
                                    </span>
                                </div>
                                <span class="load-icon">
                                    <i class="las la-sync-alt pageReload" data-bs-toggle="tooltip"
                                        title="@lang('Refresh')"></i>
                                </span>
                            </div>
                            <ul class="chat-board-left-item">
                                @forelse ($conversations as $conv)
                                    @php
                                        $isActive = $id == $conv->id;
                                        $unreadMsgCount = $conv->messages->whereNull('read_at')->count();
                                        $lastMsg = $conv->messages?->last();
                                    @endphp
                                    <li
                                        class="{{ $isActive ? 'active' : '' }} @if ($conv->status) disabled @endif">
                                        <a href="{{ route('user.conversation.index', $conv->id) }}">
                                            <span class="icon"> <img
                                                    src="{{ getImage(getFilePath('buyerProfile') . '/' . $conv->buyer->image, avatar: true) }}"
                                                    alt="img"> </span>
                                            <div class="chat-item">
                                                <h4 class="title mb-1">{{ $conv->buyer->fullname }}</h4>
                                                <p
                                                    class="desc fs-12 @if ($unreadMsgCount) text--base @else text--secondary @endif">
                                                    {{ strLimit(@$lastMsg->message, 30) }}</p>
                                                <span
                                                    class="time @if ($unreadMsgCount) text--base @else text--secondary @endif"><i
                                                        class="las la-clock"></i>
                                                    {{ diffForHumans(@$lastMsg->updated_at) }}</span>
                                            </div>
                                        </a>
                                    </li>
                                @empty
                                    <div class="empty-message text-center py-5">
                                        <img height="50" width="50"
                                            src="{{ asset('assets/images/user/avatar.png') }}" alt="empty">
                                        <span class="d-flex justify-content-center"> @lang('Wait for buyer conversations!')</span>
                                    </div>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-8 col-lg-12 col-md-7">
                        <div class="chat-box">
                            <div class="chat-box__content">
                                <div class="chat-box__thread" id="message">
                                    @forelse (@$messages ?? []  as $message)
                                        @php
                                            $styleClass = '';
                                            $profileImage = '';

                                            if ($message->admin_id) {
                                                $styleClass = 'message--left escrow';
                                                $profileImage = getImage(
                                                    getFilePath('adminProfile') . '/' . @$message->admin->image,
                                                    avatar: true,
                                                );
                                            } elseif ($message->buyer_id) {
                                                $styleClass = 'message--left';
                                                $profileImage = getImage(
                                                    getFilePath('buyerProfile') . '/' . @$message->buyer->image,
                                                    avatar: true,
                                                );
                                            } elseif ($message->user_id == auth()->id()) {
                                                $styleClass = 'message--right';
                                                $profileImage = getImage(
                                                    getFilePath('userProfile') . '/' . @$message->user->image,
                                                    avatar: true,
                                                );
                                            }
                                        @endphp
                                        <div class="single-message {{ $styleClass }}">
                                            <div class="message-content-outer">
                                                <div class="message-content" data-bs-toggle="tooltip"
                                                    @if ($styleClass == 'message--left') title="@lang('Buyer')"  @elseif($styleClass == 'message--left escrow') title="@lang('Escrow')" @else title="@lang('You')" @endif>
                                                    <p class="message-text"> {{ nl2br($message->message) }}</p>
                                                    @if ($message->files)
                                                        <small
                                                            class="message-box__text {{ $message->action == Status::YES ? 'action-message-box' : '' }}">
                                                            @php
                                                                $files = is_array($message->files)
                                                                    ? $message->files
                                                                    : json_decode($message->files, true);
                                                            @endphp
                                                            @foreach ($files as $file)
                                                                <a href="{{ route('buyer.download.attachment', encrypt(getFilePath('message') . '/' . $file)) }}"
                                                                    target="__blank">
                                                                    <i class="las la-file"></i> {{ basename($file) }}
                                                                </a>
                                                            @endforeach
                                                        </small>
                                                    @endif
                                                </div>
                                                <span
                                                    class="message-time d-block mt-2 text-end">{{ diffForHumans(@$message->updated_at) }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="empty-message text-center py-5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M14 9C14 9.53043 13.7893 10.0391 13.4142 10.4142C13.0391 10.7893 12.5304 11 12 11H6L2 15V4C2 2.9 2.9 2 4 2H12C12.5304 2 13.0391 2.21071 13.4142 2.58579C13.7893 2.96086 14 3.46957 14 4V9Z"
                                                    stroke="#5B6671" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                </path>
                                                <path
                                                    d="M18 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V22L18 18H12C11.4696 18 10.9609 17.7893 10.5858 17.4142C10.2107 17.0391 10 16.5304 10 16V15"
                                                    stroke="#5B6671" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                </path>
                                            </svg>
                                            <p class="text-muted mt-3">@lang('Chat and meet your jobs!')</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            @if (@$id)
                                <div class="chat-box__footer">
                                    <div class="chat-send-area">
                                        <div class="chat-send-field">
                                            <form class="send__msg" id="messageForm" method="POST"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <div class="files-here">
                                                    <span> @lang('Selected') <b></b> @lang('Files')<i
                                                            class="las la-times removeFile"></i></span>
                                                </div>
                                                <div class="d-flex align-center gap-2">
                                                    <div class="input-group">
                                                        <textarea class="form-control form--control" id="message" name="message" type="text" placeholder="@lang('Type your message here') ..."></textarea>
                                                        <span class="btn--base btn-sm chat-send-btn">
                                                            <label data-bs-toggle="tooltip" data-bs-placement="top"
                                                                data-bs-title="Supported Files : .jpg, .jpeg, .png, .pdf, .docx"
                                                                for="file"> <i class="las la-paperclip"></i></label>
                                                            <input class="messageFileUpload" id="file"
                                                                name="message_files[]" type="file" hidden multiple
                                                                accept="image/jpg, image/jpeg, image/png, .pdf, .docx, .doc">
                                                        </span>
                                                    </div>
                                                    <button class="chating-btn" type="submit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none">
                                                            <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor"
                                                                stroke-width="1.5" stroke-linecap="round"
                                                                stroke-linejoin="round">
                                                            </path>
                                                            <path d="M22 2L11 13" stroke="currentColor" stroke-width="1.5"
                                                                stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/global/js/pusher.min.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            $(document).ready(function() {
                scrollToBottom();
            });

            function scrollToBottom() {
                var chatBox = $(".chat-box__thread");
                if (chatBox.length > 0) {
                    chatBox.scrollTop(chatBox[0].scrollHeight);
                }
            }


            const chatBoxThread = document.querySelector('.chat-box__thread');
            if (chatBoxThread) {
                const observer = new MutationObserver(function(mutations) {
                    scrollToBottom();
                });

                observer.observe(chatBoxThread, {
                    childList: true,
                    subtree: true
                });
            }


            $('#messageForm').on('keypress', function(e) {
                if (e.which == 13 && !e.shiftKey) {
                    e.preventDefault();
                    messageSubmit();
                }
            });

            $('#messageForm').on('submit', function(e) {
                e.preventDefault();
                messageSubmit();
            });

            function messageSubmit() {
                var url = `{{ route('user.conversation.store', @$id ?? 0) }}`;
                var formData = new FormData($('#messageForm')[0]);

                $.ajax({
                    _token: "{{ csrf_token() }}",
                    type: "POST",
                    url: url,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status == 'success') {
                            $('#messageForm')[0].reset();
                            $('.files-here').removeClass('show')
                            $('.chat-box').removeClass('add-file')
                        } else {
                            notify('error', response.message);
                        }
                    },
                    error: function(response) {
                        notify('error', response);
                    }
                });
            }

            // file upload
            $(".messageFileUpload").on('change', function() {
                proPicURL(this);
            });

            function proPicURL(input) {
                if (input.files && input.files.length > 0) {
                    const files = input.files;
                    $('.files-here').addClass('show')
                    $('.chat-box').addClass('add-file')
                    $('.files-here span b').text(input.files.length);
                }
            }

            $(".removeFile").on('click', function() {
                $(".chat-box").find('input[type=file]').val('');
                $('.files-here').removeClass('show')
                $('.chat-box').removeClass('add-file')
            });


            // for pusher
            Pusher.logToConsole = true;
            var pusher = new Pusher("{{ gs()->pusher_config->app_key }}", {
                cluster: "{{ gs()->pusher_config->cluster }}",
            });

            const pusherConnection = (eventName, callback) => {

                pusher.connection.bind('connected', () => {
                    const SOCKET_ID = pusher.connection.socket_id;
                    const BASE_URL = "{{ route('home') }}";
                    const CHANNEL_NAME = `private-${eventName}`;
                    const url = `${BASE_URL}/pusher/auth/${SOCKET_ID}/${CHANNEL_NAME}`;
                    pusher.config.authEndpoint = url;
                    let channel = pusher.subscribe(CHANNEL_NAME);
                    channel.bind('pusher:subscription_succeeded', function() {
                        channel.bind(eventName, function(data) {
                            callback(data)
                        })
                    });
                });
            };

            pusherConnection('conversation_' + {{ $id ?? 0 }}, liveChat);
            let userId = {{ auth()->id() }};
            let buyerId = {{ @$conversation->buyer_id ?? 0 }};


            function diffForHumans(date) {
                const now = new Date();
                const messageDate = new Date(date);
                const diffInSeconds = Math.floor((now - messageDate) / 1000);

                if (diffInSeconds < 60) {
                    return 'Just now';
                }

                const diffInMinutes = Math.floor(diffInSeconds / 60);
                if (diffInMinutes < 60) {
                    return `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`;
                }

                const diffInHours = Math.floor(diffInMinutes / 60);
                if (diffInHours < 24) {
                    return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
                }

                const diffInDays = Math.floor(diffInHours / 24);
                if (diffInDays < 30) {
                    return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`;
                }

                const diffInMonths = Math.floor(diffInDays / 30);
                if (diffInMonths < 12) {
                    return `${diffInMonths} month${diffInMonths > 1 ? 's' : ''} ago`;
                }

                const diffInYears = Math.floor(diffInMonths / 12);
                return `${diffInYears} year${diffInYears > 1 ? 's' : ''} ago`;
            }

            function liveChat(data) {
                if (data.action) {
                    window.location.href = `{{ url()->current() }}`;
                    return true;
                }

                // Determine the style class based on sender type
                let styleClass = '';
                if (data.buyerId == buyerId) {
                    styleClass = 'message--left';
                } else if (data.userId == userId) {
                    styleClass = 'message--right';
                } else {
                    styleClass = 'message--left escrow';
                }

                // Prepare file links (if files exist)
                let fileLinks = '';
                if (data.files && data.files.length > 0) {
                    data.files.forEach((file) => {
                        let imagePath = `{{ asset(getFilePath('message')) }}/${file}`;
                        fileLinks += `<a href="${imagePath}" download>
                        <i class="las la-file"></i> ${file}
                      </a>`;
                    });
                }

                // Get current timestamp
                const currentTime = new Date();

                // Append message to the chat box
                $('.chat-box__thread').append(`
                <div class="single-message ${styleClass}">
                    <div class="message-content-outer">
                        <div class="message-content">
                            <p class="message-text">
                                ${data.action == 1 ? 'Action: ' : ''}
                                ${data.message ? niceText(data.message) : ''}
                            </p>
                                ${fileLinks ? `<small>${fileLinks}</small>` : ''}
                        </div>
                        <span class="message-time d-block mt-2 text-end">${diffForHumans(currentTime)}</span>
                    </div>
                </div>
            `);

                scrollToBottom();
            }

            function niceText(value) {
                return value.replace(/\n/g, '<br>');
            }


            $('.pageReload').on('click', function() {
                location.reload();
            });

        })(jQuery);
    </script>
@endpush


@push('style')
    <style>
        .dashboard .dashboard-body {
            margin-bottom: unset;
        }

        /* conversion */
        .message-single-profile {
            position: relative;
        }

        .load-icon {
            cursor: pointer;
        }

        .message-single-profile a {
            display: flex;
            gap: 10px;
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 8px;
            transition: .4s;
        }

        .message-single-profile a:last-child {
            border-bottom: none;
        }

        .message-single-profile a.active-message {
            background: #f0f0f0;
            border-left: 3px solid #28c76f;
        }

        .message-left-bar {
            overflow-y: scroll;
            height: 100vh;
            width: 300px;
            padding: 20px;
            position: relative;
            padding-bottom: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        @media (max-width:1199px) {
            .message-left-bar {
                width: 320px;
                position: fixed;
                height: 100vh;
                opacity: 0;
                top: 0;
                left: 0;
                visibility: hidden;
                transform: translateX(-100%);
                transition: .2s linear;
                padding: 80px 20px;
                padding-bottom: 0;
                overflow-y: scroll;
                z-index: 9991;
                background-color: hsl(var(--black));
            }

            .message-left-bar.show {
                opacity: 1;
                visibility: visible;
                transform: translateX(0);
                background: #fff;
            }
        }

        .message-single-profile a.active-message {
            background: #f0f0f0;
            border-left: 3px solid #28c76f;
            border-bottom: none;
        }

        .message-single-profile a:hover {
            background: #f0f0f0;
        }

        .message-single-profile a img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .message-single-profile a b {
            color: #4f4f4f;
            font-weight: 500;
        }

        .message-single-profile a p {
            color: #6c6c6c;
            padding: 4px 0;
        }

        .message-single-profile a span {
            color: #9b9b9b;
            font-size: 14px;
        }

        .escrow-user {
            position: sticky;
            bottom: 0;
            display: flex;
            align-items: center;
            width: 100%;
            padding: 10px;
            gap: 20px;
            border-radius: 4px;
            margin-bottom: 2px;
            border: 1px solid hsl(var(--base)/.4);
            margin-top: auto;
        }

        .escrow-user p {
            color: hsl(var(--white) / .6);
        }

        .escrow-user b {
            color: hsl(var(--white));
        }

        .escrow-user span {
            font-size: 14px;
            color: hsl(var(--base) / .5);
        }

        .escrow-user img {
            max-width: 40px;
            max-height: 40px;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .escrow-user.active-message {
            background: hsl(var(--white) / .1);
        }

        .message-box {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width:575px) {
            .message-box {
                gap: 15px;
            }
        }



        @media (max-width:575px) {
            .message-box {
                gap: 15px;
            }
        }

        .message-box.box-right .message-box__icon {
            order: 1;
        }

        .main-message-box:last-child {
            margin-bottom: 0;
        }

        .message-middle-bar {
            padding: 30px;
            height: 100vh;
            overflow-y: scroll;
            padding-bottom: 0;
            display: flex;
            flex-direction: column;
        }

        @media (max-width:575px) {
            .message-middle-bar {
                padding: 60px 10px;
                padding-bottom: 0;
            }
        }

        .message-box__icon {
            height: 40px;
            width: 40px;
            border: 1px solid #ddd;
            border-radius: 50%;
        }

        .message-box__icon img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }


        .message-box.box-right .message-box__text span {
            justify-content: flex-end;
        }

        .message-box.box-right .message-box__text {
            text-align: right;
        }


        .message-box.box-right .message-box__text {
            margin-left: auto;
        }



        .message-box.scrow-message .message-box__text {
            background-color: hsl(var(--base));
            color: hsl(var(--black));
        }



        .chat-box .form--control {
            height: 70px;
            resize: none !important;
            width: calc(100% - 100px);
            border: 0 !important;
            box-shadow: none;
        }

        .chat-box__icon {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 24px;
            z-index: 9;
            width: 100px;
        }

        .chat-box__icon-btn {
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #28c76f;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @media (max-width:1399px) {
            .message-right-bar {
                width: 300px;
                position: fixed;
                height: 100vh;
                opacity: 0;
                right: 0;
                top: 0;
                visibility: hidden;
                transform: translateX(100%);
                transition: .2s linear;
                padding: 60px 20px;
                padding-bottom: 0;
                overflow-y: scroll;
                z-index: 9991;
            }

            .message-right-bar.show {
                opacity: 1;
                visibility: visible;
                transform: translateX(0);
            }
        }

        .message-user-profile__thumb {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .message-user-profile__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .message-right-bar__close-icon,
        .close-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            border: 1px solid #000;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: .2s linear;
            color: hsl(var(--white));
        }

        .message-right-bar__close-icon,
        .close-icon:hover {
            background-color: red;
            border-color: transparent;
            color: #fff !important;
        }

        .close-icon {
            display: none;
        }

        @media (max-width:1399px) {
            .close-icon {
                display: flex;
                left: 20px;
            }

            .message-right-bar__close-icon {
                display: none;
            }
        }

        .message-left-bar__close-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            border: 1px solid #282828;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: .2s linear;
            color: #282828;
        }

        .message-left-bar__close-icon:hover {
            background-color: #eb2222;
            border-color: transparent;
            color: #fff;
        }

        .user-online-status {
            text-align: center;
            margin-top: 20px;
            position: relative;
        }

        .user-online-status::before {
            position: absolute;
            content: "";
            width: 12px;
            height: 12px;
            border-radius: 50%;
            top: 50%;
            left: -20px;
            transform: translateY(-50%);
            background: hsl(var(--white)/.6);
        }

        .user-online-status.online::before {
            background: #2ace73;
        }

        .user-name {
            display: block;
            margin-top: 10px;
            color: hsl(var(--white));
        }

        .message-user-profile {
            text-align: center;
        }

        .left-sidebar__filter {
            background: #282828;
            font-size: 24px;
            color: #fff;
            border-radius: 4px;
            width: 45px;
            height: 32px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 25px;
            left: 0;
            z-index: 9;
            cursor: pointer;
        }

        .right-sidebar__filter {
            background: #282828;
            font-size: 24px;
            color: #fff;
            border-radius: 4px;
            width: 45px;
            height: 32px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 45px;
            right: 0;
            z-index: 9;
            cursor: pointer;
        }

        .chat-box__icon label {
            cursor: pointer;
        }

        .chat-box__icon i {
            font-size: 20px;
        }

        .chat-box__icon label {
            margin-bottom: 0 !important;
        }

        .dual-users {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .dual-users a {
            color: #4f4f4f;
            padding: 10px 15px;
            border: 1px solid #4f4f4f;
            border-radius: 4px;
        }

        .dual-users a.user-active {
            color: #fff;
            border-color: #28c76f;
            background: #28c76f;
        }

        .deal-listing-details {
            margin-top: 25px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 3px;
        }

        .sidebar-overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            content: "";
            left: 0;
            top: 0;
            background-color: rgba(0, 0, 0, 0.1);
            z-index: 9991;
            transition: 0.2s linear;
            visibility: hidden;
            opacity: 0;
        }

        .sidebar-overlay.show {
            visibility: visible;
            opacity: 1;
            z-index: 991;
        }

        .message-box__text span {
            display: flex;
            gap: 20px;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }


        .chat-box .files-here span {
            background: #6c6c6c;
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            position: relative;
            padding-right: 35px;
            margin-right: 13px;
            overflow: hidden !important;
        }

        .chat-box .files-here span i {
            cursor: pointer;
            background-color: red;
            color: #fff;
            position: absolute;
            height: 100%;
            right: 0;
            padding: 0 5px;
            top: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .files-here span b {
            font-weight: 500;
        }

        .chat-box__footer .files-here {
            position: absolute;
            bottom: 74px;
            left: 15px;
            top: unset;
            margin-bottom: 0;
            opacity: 0;
            visibility: hidden;
        }

        .chat-box__footer .files-here.show {
            visibility: visible;
            opacity: 1;
        }

        .deal-info-top {
            border: 1px solid #6c6c6c;
            border-radius: 10px;
            padding: 20px;
            background: #ffffff;
        }

        .deal-info-top .escrow-step {
            background: hsla(228, 18.5%, 10.6%, 0.89);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .deal-info-top {
            margin-bottom: 20px;
        }

        .already-paid-page {
            margin-bottom: 20px;
            border: 1px solid #ff00008f;
            padding: 15px 20px;
            border-radius: 10px;
            background: #ff00008f;
        }

        .already-paid-page span {
            color: #ffffff;
        }

        .message-box__text.action-message-box {
            color: rgb(255, 114, 0);
        }

        .deal-action-page {
            border: 1px solid #ff8510;
            padding: 10px;
            border-radius: 10px;
            position: sticky;
            top: -28px;
            background: #ffffff;
            z-index: 8;
        }

        .message-box.message-buyer .message-box__text {
            background-color: #d1f7e8;
            color: #333;
            border-left: 3px solid hsl(var(--primary)/ .4);
        }

        .message-box.scrow-message .message-box__text {
            background-color: #f7f7f7;
            color: #666;
            border-left: 3px solid hsl(var(--danger)/ .5);
        }

        .message-box.box-right .message-box__text {
            background-color: #e6f7ff;
            color: #333;
            border-left: 3px solid hsl(var(--base)/ .6);
        }

        .message-right-bar {
            text-align: right;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            height: 100vh;
        }

        .message-right-bar img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .message-right-bar h6 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .message-box.message-buyer .message-box__text span {
            justify-content: flex-end;
        }

        .message-box.message-buyer .message-box__text {
            text-align: right;
        }

        .message-box.message-buyer .message-box__text::after {
            position: absolute;
            content: "";
            top: 50%;
            left: -8px;
            transform: translateY(-50%) rotate(45deg);
            width: 20px;
            height: 20px;
            background: #d1f7e8;
            z-index: -1;
        }

        .conversation-link.disabled,
        .chat-box.disabled {
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.6;
            text-decoration: none;
            color: #aaa !important;
        }

        .conversation-wrapper {
            display: flex;
            gap: 20px;
            align-items: flex-start
        }

        .conversation-body {
            width: calc(100% - 300px);
        }

        /* conversion end */


        /* ================================= Message Css End =========================== */
        .chat-board-left-item li a {
            display: flex;
            gap: 16px;
            padding: 15px 24px;
        }

        @media (max-width:1399px) {
            .chat-board-left-item li a {
                padding: 15px;
            }
        }

        .chat-board-left-item li a .icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .chat-board-left-item li a .icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .chat-box__thread {
            height: calc(100vh - 260px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .chat-box__content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-box {
            display: flex;
            flex-direction: column;
        }

        @media (max-width:991px) {
            .chat-box__thread {
                height: calc(100vh - 300px);
            }
        }

        .chat-box__footer {
            margin-top: auto;
            background: #fff;
            padding: 15px;
            border-top: 1px solid #eee;
        }

        .single-message {
            margin-bottom: 15px;
        }

        .empty-message {
            margin: auto;
        }

        .chat-board-left-item li.disabled {
            cursor: not-allowed;
            background-color: #d5d6d6;
        }
    </style>
@endpush
