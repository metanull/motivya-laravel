## Epic 1: Foundation & Identity

- Laravel setup
- Authentication & Roles
- Admin Portal: review, accept, deny new user registration

## Epic 2: The Coach "Business-in-a-Box"

- Onboarding: Profile creation + KYC (linking to Stripe Express).
- Session Builder: Form for coaches to set price, location (Postal Code), and Min/Max participants.
- Calendar: Dashboard for coaches to see upcoming confirmed vs. pending sessions.

## Epic 3: The Athlete Experience & Payments

- Discovery: Search/Filter engine for sessions in Brussels (filtered by MySQL distance queries).
- Stripe Integration: Payment flow supporting Bancontact and Credit Cards.
- Booking Logic: Handling "Tentative" bookings until the minimum participant threshold is reached.

## Epic 4: Accountant Portal + Automated Invoicing & PEPPOL

- Invoicing Logic: Integration with Stripe Invoicing API to trigger PEPPOL XML generation upon session completion.
- VAT Engine: Logic to calculate the correct payout for VAT-registered vs. non-subject coaches.
- Refund Logic: Automated 48h/24h cancellation policies.
- View-only access for the accountant to export CSV/Excel summaries of all Stripe-generated invoices.

## Epic 5: Analytics & QA

- Analytics: Performance dashboards for coaches and global platform stats for admins.
- QA: End-to-end testing of the "Session Completed → Invoice Sent" loop.