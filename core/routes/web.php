<?php

use Illuminate\Support\Facades\Route;

Route::post('pusher/auth/{socketId}/{channelName}', 'SiteController@pusher')->name('pusher');

Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});



// User Support Ticket
Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group(function () {
    Route::get('/', 'supportTicket')->name('index');
    Route::get('new', 'openSupportTicket')->name('open');
    Route::post('create', 'storeSupportTicket')->name('store');
    Route::get('view/{ticket}', 'viewTicket')->name('view');
    Route::post('reply/{id}', 'replyTicket')->name('reply');
    Route::post('close/{id}', 'closeTicket')->name('close');
    Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
});

Route::get('app/deposit/confirm/{hash}', 'Gateway\PaymentController@appDepositConfirm')->name('deposit.app.confirm');

Route::controller('JobExploreController')->group(function () {
    Route::get('freelance-jobs', 'freelanceJobs')->name('freelance.jobs');
    Route::get('freelance-filter-jobs', 'filterJobs')->name('freelance.filter.jobs');
    Route::get('explore-job/{slug}', 'exploreJob')->name('explore.bid.job');

    Route::get('explore-get-similar-freelancers', 'getSimilarFreelancers')->name('explore.get-similar-freelancers');
    Route::get('explore-get-similar-jobs', 'getSimilarJobs')->name('explore.get-similar-jobs');
    
    //talent-area
    Route::get('talent/details/{username}', 'exploreFreelancer')->name('talent.explore');
});

Route::controller('SiteController')->group(function () {

    Route::get('talents', 'allFreelancers')->name('all.freelancers');

    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');
    Route::post('subscribe', 'subscribe')->name('subscribe');

    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');

    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');

    Route::get('blogs', 'blogs')->name('blogs');
    Route::get('blog/{slug}', 'blogDetails')->name('blog.details');

    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');

    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
    Route::get('maintenance-mode', 'maintenance')->withoutMiddleware('maintenance')->name('maintenance');

    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', 'index')->name('home');
});
