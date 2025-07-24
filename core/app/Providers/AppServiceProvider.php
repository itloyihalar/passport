<?php

namespace App\Providers;

use App\Constants\Status;
use App\Lib\Searchable;
use App\Models\AdminNotification;
use App\Models\Buyer;
use App\Models\Deposit;
use App\Models\Frontend;
use App\Models\Job;
use App\Models\Project;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Builder::mixin(new Searchable);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!cache()->get('SystemInstalled')) {
            $envFilePath = base_path('.env');
            if (!file_exists($envFilePath)) {
                header('Location: install');
                exit;
            }
            $envContents = file_get_contents($envFilePath);
            if (empty($envContents)) {
                header('Location: install');
                exit;
            } else {
                cache()->put('SystemInstalled', true);
            }
        }


        $viewShare['emptyMessage'] = 'Data not found';
        view()->share($viewShare);


        view()->composer('admin.partials.sidenav', function ($view) {
            $view->with([
                'jobPendingCount'  => Job::pending()->where('status', Status::JOB_PUBLISH)->count(),
                'jobRejectedCount' => Job::rejected()->count(),
                'jobDraftedCount'  => Job::drafted()->count(),

                'projectReportedCount'  => Project::reported()->count(),

                'incompleteProfileUsersCount'  => User::incompleteProfile()->count(),
                'bannedUsersCount'           => User::banned()->count(),
                'emailUnverifiedUsersCount' => User::emailUnverified()->count(),
                'mobileUnverifiedUsersCount'   => User::mobileUnverified()->count(),
                'kycUnverifiedUsersCount'   => User::kycUnverified()->count(),
                'kycPendingUsersCount'   => User::kycPending()->count(),

                'bannedBuyersCount'   => Buyer::banned()->count(),
                'emailUnverifiedBuyersCount' => Buyer::emailUnverified()->count(),
                'mobileUnverifiedBuyersCount'   => Buyer::mobileUnverified()->count(),
                'kycUnverifiedBuyersCount'   => Buyer::kycUnverified()->count(),
                'kycPendingBuyersCount'   => Buyer::kycPending()->count(),

                'pendingTicketCount'   => SupportTicket::whereIN('status', [Status::TICKET_OPEN, Status::TICKET_REPLY])->count(),
                'pendingDepositsCount'    => Deposit::pending()->count(),
                'pendingWithdrawCount'    => Withdrawal::pending()->count(),
                'updateAvailable'    => version_compare(gs('available_version'), systemDetails()['version'], '>') ? 'v' . gs('available_version') : false,
            ]);
        });

        view()->composer('admin.partials.topnav', function ($view) {
            $view->with([
                'adminNotifications' => AdminNotification::where('is_read', Status::NO)->with('user')->orderBy('id', 'desc')->take(10)->get(),
                'adminNotificationCount' => AdminNotification::where('is_read', Status::NO)->count(),
            ]);
        });




        view()->composer('partials.seo', function ($view) {
            $seo = Frontend::where('data_keys', 'seo.data')->first();
            $view->with([
                'seo' => $seo ? $seo->data_values : $seo,
            ]);
        });


        view()->composer(activeTemplate() . 'layouts.buyer_master', function ($view) {
            $unreadCount = 0;
            $buyerGuard = auth()->guard('buyer');
            if ($buyerGuard->check()) {
                $buyer = $buyerGuard->user();

                $unreadCount = Message::whereHas('conversation', function ($query) use ($buyer) {
                    $query->where('buyer_id', $buyer->id);
                })
                    ->whereNull('buyer_read_at')
                    ->count();
            }
            $view->with('unreadCount', $unreadCount);
        });


        view()->composer(activeTemplate() . 'layouts.master', function ($view) {
            $unreadCount = 0;
            if (auth()->check()) {
                $user = auth()->user();
                $unreadCount = Message::whereHas('conversation', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                    ->whereNull('read_at')->count();
            }
            $view->with('unreadCount', $unreadCount);
        });


        if (gs('force_ssl')) {
            \URL::forceScheme('https');
        }


        Paginator::useBootstrapFive();
    }
}
