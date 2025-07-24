@extends($activeTemplate . 'layouts.frontend')
@section('content')
    @include('Template::partials.banner')

    @if (@$sections->secs != null)
        @foreach (json_decode($sections->secs) as $sec)
            @include($activeTemplate . 'sections.' . $sec)
        @endforeach
    @endif
@endsection

@push('style')
    <style>
        .empty-message {
            background: unset;
            border: 1px solid hsl(var(--white));
        }
    </style>
@endpush
