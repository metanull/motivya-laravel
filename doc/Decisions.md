## Development Stack - Backend

Laravel 12 (PHP 8.2+):
- UI: Livewire + Blade
- Must support email/password authentication
- Must support social platform authentication (google, at the least)
- Must support MFA
- Must support "Roles"
- Must provide authentication to API (to support development of custom/independant frontend only or mobile apps)
- Cashier (Stripe support)

## Database

We will use a Relational DBMS: MySQL in prod; sqlite in dev/test

## Payments

We will use Stripe (ciustomer has an account)
We must support Bancontact Pay payments (through Stripe)
We must issue PEPPOL invoices to coaches (in complianc,e with Belgian Law)
All coaches have a Entreprise Number, but not all are subject to VAT.

Stripe sends callback requests!

## Storage

Use S3 compatible storage in prod (OVH, Laravel Cloud); filesystem in dev/test

## Cache

Use Valkey compatible storage in prod (OVH, Laravel Cloud); filesystem in dev/test

## Development Stack - Mobile-first front-end end-user app 

> It must not be implemented now. This is just for information!

React 19 PWA application