@php  use Carbon\Carbon; @endphp

@forelse ($jobs as $job)
    <div class="expert-developer">
        <div class="expert-developer__top">
            <div class="left">
                <div class="left__top">
                    <h6 class="expert-developer__title">
                        <a href="{{ route('explore.bid.job', $job->slug) }}">
                            {{ strLimit(__($job->title), 100) }}
                        </a>
                    </h6>

                </div>
                <span class="expert-developer__time">
                    {{ getJobTimeDifference($job->created_at, $job->deadline) }}
                </span>
                <div class="job-information-area">
                    <div>
                        <span class="title"> @lang('Budget') <sup> [@if ($job->custom_budget)
                                    @lang('Customized')
                                @else
                                    @lang('Fixed')
                                @endif] </sup></span>
                        <p class="text"> {{ showAmount($job->budget) }} </p>
                    </div>
                    <div>
                        <span class="title"> @lang('Experience level') </span>
                        <p class="text">
                            @if ($job->skill_level == Status::SKILL_PRO)
                                @lang('Pro Level')
                            @elseif($job->skill_level == Status::SKILL_EXPERT)
                                @lang('Expert')
                            @elseif($job->skill_level == Status::SKILL_INTERMEDIATE)
                                @lang('Intermediate')
                            @else
                                @lang('Entry')
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="right">
                <a href="{{ route('explore.bid.job', $job->slug) }}" target="_blank" class="btn btn--base btn--xsm">
                    @lang('Bid Now')
                </a>
                <p class="total-bid mt-1">
                    <span class="text"> @lang('Bids'): {{ $job->bids_count }} </span>
                </p>
            </div>
        </div>
        <p class="expert-developer__desc">
            @php echo strLimit(strip_tags($job->description), 230) @endphp
        </p>

        <!-- Displaying job skills -->
        <ul class="skill-list justify-content-start">
            @foreach ($job->skills as $skill)
                <li class="skill-list__item">
                    <a href="javascript:void(0)" class="skill-list__link">
                        {{ __($skill->name) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@empty
    <div class="empty-message text-center py-5">
        <img src="{{ asset($activeTemplateTrue . 'images/empty.png') }}" alt="empty">
        <p class="text-muted mt-3">@lang('No job found!')</p>
    </div>
@endforelse


@if ($jobs->hasPages())
    {{ $jobs->links('pagination::bootstrap-5') }}
@endif
