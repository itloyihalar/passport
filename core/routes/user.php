<?php

use Illuminate\Support\Facades\Route;

Route::namespace('User\Auth')->name('user.')->middleware('guest')->group(function () {
    Route::controller('LoginController')->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login');
        Route::get('logout', 'logout')->middleware('auth')->withoutMiddleware('guest')->name('logout');
    });

    Route::controller('RegisterController')->group(function () {
        Route::get('register', 'showRegistrationForm')->name('register');
        Route::post('register', 'register');
        Route::post('check-user', 'checkUser')->name('checkUser')->withoutMiddleware('guest');
    });

    Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
        Route::get('reset', 'showLinkRequestForm')->name('request');
        Route::post('email', 'sendResetCodeEmail')->name('email');
        Route::get('code-verify', 'codeVerify')->name('code.verify');
        Route::post('verify-code', 'verifyCode')->name('verify.code');
    });

    Route::controller('ResetPasswordController')->group(function () {
        Route::post('password/reset', 'reset')->name('password.update');
        Route::get('password/reset/{token}', 'showResetForm')->name('password.reset');
    });

    Route::controller('SocialiteController')->group(function () {
        Route::get('social-login/{provider}', 'socialLogin')->name('social.login');
        Route::get('social-login/callback/{provider}', 'callback')->name('social.login.callback');
    });
});

Route::middleware('auth')->name('user.')->group(function () {

    Route::get('user-data', 'User\UserController@userData')->name('data');
    Route::post('user-data-submit', 'User\UserController@userDataSubmit')->name('data.submit');

    //authorization
    Route::middleware('registration.complete')->namespace('User')->controller('AuthorizationController')->group(function () {
        Route::get('authorization', 'authorizeForm')->name('authorization');
        Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
        Route::post('verify-email', 'emailVerification')->name('verify.email');
        Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
        Route::post('verify-g2fa', 'g2faVerification')->name('2fa.verify');
    });

    Route::middleware(['check.status', 'registration.complete'])->group(function () {

        Route::namespace('User')->group(function () {

            Route::controller('UserController')->group(function () {
                Route::get('dashboard', 'home')->name('home');
                Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');

                //2FA
                Route::get('twofactor', 'show2faForm')->name('twofactor');
                Route::post('twofactor/enable', 'create2fa')->name('twofactor.enable');
                Route::post('twofactor/disable', 'disable2fa')->name('twofactor.disable');

                //KYC
                Route::get('kyc-form', 'kycForm')->name('kyc.form');
                Route::get('kyc-data', 'kycData')->name('kyc.data');
                Route::post('kyc-submit', 'kycSubmit')->name('kyc.submit');

                //Report
                Route::any('deposit/history', 'depositHistory')->name('deposit.history');
                Route::get('transactions', 'transactions')->name('transactions');

                Route::post('add-device-token', 'addDeviceToken')->name('add.device.token');
            });

            //Profile setting
            Route::controller('ProfileController')->group(function () {
                Route::get('change-password', 'changePassword')->name('change.password');
                Route::post('change-password', 'submitPassword');


                Route::get('profile-skill', 'skill')->name('profile.skill');
                Route::post('profile-skill-store', 'submitSkills')->name('store.profile.skill');

                Route::get('profile-setting', 'profile')->name('profile.setting');
                Route::post('profile-setting', 'submitProfile')->name('store.profile.setting');

                Route::get('profile-education', 'education')->name('profile.education');
                Route::post('profile-education-store', 'submitEducations')->name('store.profile.education');

                Route::get('profile-portfolio', 'portfolio')->name('profile.portfolio');
                Route::post('store-profile-portfolio/{id?}', 'submitPortfolios')->name('store.profile.portfolio');
                Route::post('status-profile-portfolio/{id}', 'statusPortfolio')->name('status.profile.portfolio');

                Route::post('work-profile-complete', 'workProfileComplete')->name('profile.complete');
            });

            Route::controller('BidController')->prefix('bid')->name('bid.')->group(function () {
                Route::get('list', 'index')->name('index');
                Route::post('store/{id}', 'storeBid')->name('store');
                Route::post('withdraw/{id}', 'withdrawBid')->name('withdraw');
            });

            Route::controller('ProjectController')->prefix('project')->name('project.')->group(function () {
                Route::get('index', 'index')->name('index');
                Route::get('detail/{project_id}', 'detail')->name('detail');
                Route::get('file/download/{project_id}/{file}', 'downloadFile')->name('file.download');

                Route::get('upload-form/{project_id}', 'projectUploadForm')->name('form');
                Route::post('upload/{project_id}', 'projectUpload')->name('upload');
                
                Route::post('store/review-rating/{project_id}', 'storeReviewRating')->name('store.review-rating');
            });


            //Conversation
            Route::controller('ConversationController')->prefix('conversation')->name('conversation.')->group(function () {
                Route::get('/{conversation_id?}', 'index')->name('index');
                Route::post('store/{conversation_id}', 'conversationStore')->name('store');
            });

            // Withdraw
            Route::controller('WithdrawController')->prefix('withdraw')->name('withdraw')->group(function () {
                Route::middleware('kyc')->group(function () {
                    Route::get('/', 'withdrawMoney');
                    Route::post('/', 'withdrawStore')->name('.money');
                    Route::get('preview', 'withdrawPreview')->name('.preview');
                    Route::post('preview', 'withdrawSubmit')->name('.submit');
                });
                Route::get('history', 'withdrawLog')->name('.history');
            });
        });
    });
});
