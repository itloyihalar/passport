<div class="dashboard-table">
    <table class="table table--responsive--md">
        <thead>
            <tr>
                <th>@lang('Job')</th>
                <th> @lang('Buyer') </th>
                <th> @lang('Estimate Time') </th>
                <th> @lang('Budget') </th>
                <th> @lang('Status') </th>
                @if (!request()->routeIs('user.home'))
                    <th> @lang('Action') </th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($bids as $bid)
                <tr>
                    <td>
                        <a class="clamping"
                            href="@if ($bid->job->status == Status::JOB_PUBLISH && $bid->job->is_approved == Status::JOB_APPROVED) {{ route('explore.bid.job', $bid->job->slug) }} @else javascript:void(0) @endif"
                            target="__blank">{{ __($bid->job->title) }}</a>
                    </td>
                    <td>
                        <div>
                            {{ $bid->buyer->fullname }}
                            <span class="small d-block">
                                <a href="{{ route('freelance.jobs', ['buyer' => $bid->buyer->username]) }}"
                                    target="__blank"><span>@</span>{{ $bid->buyer->firstname }}</a>
                            </span>
                        </div>
                    </td>
                    <td><span class="clamping">{{ __($bid->estimated_time) }}</span></td>
                    <td>
                        <div>
                            {{ showAmount($bid->bid_amount) }}<br>
                            <sup class="text--primary">
                                [
                                @if ($bid->job->custom_budget)
                                    @lang('Customized')
                                @else
                                    @lang('Fixed')
                                @endif
                                ]
                            </sup>
                        </div>
                    </td>
                    <td> @php echo $bid->statusBadge @endphp</td>
                    @if (!request()->routeIs('user.home'))
                        <td>
                            <div class="action-btn">
                                <button class="action-btn__icon">
                                    <i class="fa-solid fa-caret-down"></i>
                                </button>
                                <ul class="action-dropdown">
                                    @if ($bid->status == Status::BID_PENDING)
                                        <li class="action-dropdown__item withdrawModalBtn"
                                            data-action="{{ route('user.bid.withdraw', $bid->id) }}"
                                            data-question="@lang('Are you sure to withdraw this job proposal / bid?')">
                                            <a class="action-dropdown__link" href="javascript:void(0)">
                                                <span class="text"><i class="las la-undo"></i>
                                                    @lang('Withdraw')</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li class="action-dropdown__item moreModalBtn"
                                        data-title="{{ __($bid->job->title) }}"
                                        data-freelancer="{{ $bid->user->fullname }}"
                                        data-quote="{{ __(@$bid->bid_quote) }}"><a class="action-dropdown__link"
                                            href="javascript:void(0)">
                                            <span class="text"><i class="las la-quote-left"></i>
                                                @lang('My Quote')</span>
                                        </a>
                                    </li>

                                    @if (@$bid->project && @$bid->project?->status != Status::PROJECT_COMPLETED)
                                        <li class="action-dropdown__item">
                                            <a class="action-dropdown__link"
                                                href="{{ route('user.project.detail', @$bid->project->id) }}">
                                                <span class="text"><i class="las la-desktop"></i>
                                                    @lang('Project Details')
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="100%" class="text-center msg-center">
                        @include('Template::partials.empty', ['message' => 'Bid not found!'])
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
