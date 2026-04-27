<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\BookingRefunded;
use App\Events\CoachApproved;
use App\Events\CoachPayoutProcessed;
use App\Events\CoachRejected;
use App\Events\NewCoachApplication;
use App\Events\SessionCancelled;
use App\Events\SessionCompleted;
use App\Events\SessionConfirmed;
use App\Listeners\GenerateCreditNoteOnRefund;
use App\Listeners\GenerateInvoiceOnSessionCompletion;
use App\Listeners\NotifyAdminsOfNewApplication;
use App\Listeners\RefundAllBookingsOnSessionCancellation;
use App\Listeners\SendBookingCancelledNotification;
use App\Listeners\SendBookingConfirmedNotification;
use App\Listeners\SendCoachApprovedNotification;
use App\Listeners\SendCoachRejectedNotification;
use App\Listeners\SendPayoutNotification;
use App\Listeners\SendSessionCancelledNotification;
use App\Listeners\SendSessionConfirmedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
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
        NewCoachApplication::class => [
            NotifyAdminsOfNewApplication::class,
        ],
        SessionCancelled::class => [
            RefundAllBookingsOnSessionCancellation::class,
            SendSessionCancelledNotification::class,
        ],
        SessionConfirmed::class => [
            SendSessionConfirmedNotification::class,
        ],
        CoachPayoutProcessed::class => [
            SendPayoutNotification::class,
        ],
        SessionCompleted::class => [
            GenerateInvoiceOnSessionCompletion::class,
        ],
        BookingCreated::class => [
            SendBookingConfirmedNotification::class,
        ],
        BookingCancelled::class => [
            SendBookingCancelledNotification::class,
        ],
        BookingRefunded::class => [
            GenerateCreditNoteOnRefund::class,
        ],
    ];
}
