<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CoachApproved;
use App\Events\CoachRejected;
use App\Listeners\SendCoachApprovedNotification;
use App\Listeners\SendCoachRejectedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        CoachApproved::class => [
            SendCoachApprovedNotification::class,
        ],
        CoachRejected::class => [
            SendCoachRejectedNotification::class,
        ],
    ];
}
