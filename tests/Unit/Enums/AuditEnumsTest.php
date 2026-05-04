<?php

declare(strict_types=1);

use App\Enums\AuditActorType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\AuditSource;

describe('AuditEventType', function () {

    it('has all expected stable event type values', function () {
        $values = array_column(AuditEventType::cases(), 'value');

        expect($values)->toContain('coach.application_submitted');
        expect($values)->toContain('coach.approved');
        expect($values)->toContain('coach.rejected');
        expect($values)->toContain('user.created_by_admin');
        expect($values)->toContain('user.role_changed');
        expect($values)->toContain('user.suspended');
        expect($values)->toContain('user.reactivated');
        expect($values)->toContain('session.created');
        expect($values)->toContain('session.updated');
        expect($values)->toContain('session.published');
        expect($values)->toContain('session.cancelled');
        expect($values)->toContain('session.completed');
        expect($values)->toContain('session.deleted');
        expect($values)->toContain('booking.created');
        expect($values)->toContain('booking.payment_started');
        expect($values)->toContain('booking.payment_confirmed');
        expect($values)->toContain('booking.payment_failed');
        expect($values)->toContain('booking.cancelled');
        expect($values)->toContain('booking.expired');
        expect($values)->toContain('refund.requested');
        expect($values)->toContain('refund.completed');
        expect($values)->toContain('refund.failed');
        expect($values)->toContain('invoice.generated');
        expect($values)->toContain('invoice.credit_note_generated');
        expect($values)->toContain('invoice.xml_downloaded');
        expect($values)->toContain('payout_statement.generated');
        expect($values)->toContain('payout_statement.submitted');
        expect($values)->toContain('payout_statement.approved');
        expect($values)->toContain('payout_statement.blocked');
        expect($values)->toContain('payout_statement.paid');
        expect($values)->toContain('anomaly.resolved');
        expect($values)->toContain('anomaly.ignored');
    });

    it('can be resolved from its string value', function () {
        expect(AuditEventType::from('session.created'))->toBe(AuditEventType::SessionCreated);
        expect(AuditEventType::from('booking.payment_confirmed'))->toBe(AuditEventType::BookingPaymentConfirmed);
        expect(AuditEventType::from('refund.requested'))->toBe(AuditEventType::RefundRequested);
    });

});

describe('AuditOperation', function () {

    it('has the expected string-backed values', function () {
        expect(AuditOperation::Create->value)->toBe('create');
        expect(AuditOperation::Update->value)->toBe('update');
        expect(AuditOperation::StateChange->value)->toBe('state_change');
        expect(AuditOperation::Delete->value)->toBe('delete');
        expect(AuditOperation::Payment->value)->toBe('payment');
        expect(AuditOperation::Refund->value)->toBe('refund');
        expect(AuditOperation::Export->value)->toBe('export');
        expect(AuditOperation::Security->value)->toBe('security');
    });

    it('has exactly eight cases', function () {
        expect(AuditOperation::cases())->toHaveCount(8);
    });

});

describe('AuditActorType', function () {

    it('has the expected string-backed values', function () {
        expect(AuditActorType::User->value)->toBe('user');
        expect(AuditActorType::System->value)->toBe('system');
        expect(AuditActorType::Stripe->value)->toBe('stripe');
        expect(AuditActorType::Scheduler->value)->toBe('scheduler');
        expect(AuditActorType::Console->value)->toBe('console');
        expect(AuditActorType::Queue->value)->toBe('queue');
    });

    it('has exactly six cases', function () {
        expect(AuditActorType::cases())->toHaveCount(6);
    });

});

describe('AuditSource', function () {

    it('has the expected string-backed values', function () {
        expect(AuditSource::Web->value)->toBe('web');
        expect(AuditSource::Console->value)->toBe('console');
        expect(AuditSource::Queue->value)->toBe('queue');
        expect(AuditSource::Webhook->value)->toBe('webhook');
        expect(AuditSource::Scheduler->value)->toBe('scheduler');
        expect(AuditSource::Test->value)->toBe('test');
    });

    it('has exactly six cases', function () {
        expect(AuditSource::cases())->toHaveCount(6);
    });

});
