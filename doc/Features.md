## Introduction

Motivya is a specialized marketplace for the Brussels sports community. It serves as a bridge between Coaches (service providers) and Athletes (end-users), automating the entire lifecycle of a sports session: from discovery and booking to secure payment and legal e-invoicing.

## The Core Value Proposition

- For Athletes: Localized discovery of sports sessions with a seamless "few-click" booking and secure payment experience.
- For Coaches: A "business-in-a-box" platform that handles scheduling, marketing (via social push), and complex Belgian tax/PEPPOL compliance.
- For the Platform: A scalable commission-based model with automated financial oversight.

## User Profiles & Feature Matrix

Based on your use cases, the platform revolves around four distinct roles:

| Profile | Key Features |
| Coach | Profile/Specialty management, geo-fenced session creation (min/max participants), automated revenue tracking, social media pushing (WhatsApp/FB), and Ical/Google sync. |
| Athlete | Session discovery (based on location if available - basic), advanced filtering (sport/level/time), 1-click booking, secure online payment, and automated reminders. |
| Accountant | PEPPOL-mandatory invoice routing, automated VAT adjustment for non-subject coaches, financial export (Excel/CSV), and anomaly detection. |
| Admin | Coach validation (KYC), commission management, platform performance monitoring, and dispute resolution. |

## Financial & Invoicing Logic

A critical requirement is the PEPPOL BIS 3.0 compliance mandatory in Belgium from Jan 2026.

- The Flow: Payment is captured via Stripe Connect. Upon session completion, Billit (via the Stripe App) automatically routes a structured XML invoice to the customer and a commission invoice to the coach.
- VAT Neutralization: The system must automatically adjust coach payouts based on their VAT status. If a coach is non-subject, the platform adjusts the "Coach Payment" formula to maintain Motivya's margin despite the inability to recover VAT.

> Paiement Coach = Revenu HTVA − Marge Cible HTVA

## Session Management (Coach)

- Create sessions with activity, level, date, time, location, capacity, and price
- Activity-specific cover images (images are assets uplaoded by the admin, coaches only selects from within these images)
- Edit and delete sessions
- Recurring weekly sessions ("Every Wednesday at 19:00")

## Session Discovery (Client)

- Interactive map with session markers and cluster support
- Search by city or postal code or by Geolocation (if user allows) detection within 2 km radius
- Filters – activity type, level (beginner / intermediate / advanced), date, time range
- Activity categories – Yoga, Strength, Running, Cardio, Pilates, Outdoor, Boxing, Dance, Padel Lessons, Tennis Lessons...

## Booking & Favourites (Client)

- One-click booking with capacity tracking (must be atomic, to prevent overflowing the capacity)
- Favourites saved per user with optional push notifications per session
- WhatsApp share button and copy-link on session detail pages

## User Dashboard (Client)

- Upcoming and past bookings overview
- Shortcut to explore, favourites, and profile

### Display Outfit Recommender (Client - Nice to have/LOW priority)

- Real-time weather from WeatherAPI.com (60-minute server-side cache)
- Display "recommended outfit" per weather bucket (cold / mild / warm / rain) + Contextual safety tips (e.g., UV protection when hot, visibility gear at night)

### Community & Marketing (Client - Nice to have/LOW priority)

- Dynamic hero counters: participants, local coaches, sessions this week
- Geolocation-driven city detection for counters
- "Activities Near You" section with social proof ("X people already participating")
- "Weekly Sports Events" section highlighting recurring sessions
- Community newsletter subscription with activity preferences

## Dashboard & Tools (Coach)

- Session statistics (bookings, fill rate, revenue)
- Calendar view of upcoming sessions
- Revenue simulator (gated for non-authenticated visitors)
- 3-step coach application form with admin review workflow
- Verification badge once approved by admin

## Admin Tools (Admin)

- Review, approve, and reject coach applications
- Database CSV export (coaches, sessions, payments)

### Manage Outfit Recommender (Admin - Nice to have/LOW priority)

- Activity selector: General, Running, Outdoor Fitness, Walking, Mobility, Gentle Session
- Weather bucket selector: (cold / mild / warm / rain)
- Sections: Top, Layer, Bottom, Shoes, Accessories & Equipment
- Contextual safety tips (e.g., UV protection when hot, visibility gear at night)
- Upload Flat-lay product imagery styled in Motivya brand colours

## Internationalisation

- Language/Locale: French fr-BE (default), English en-UK, Dutch nl-BE
- Automatic browser language/locale detection, fallback to fr-BE 
- All UI strings, dates, and category labels translated
- GDPR/Privacy page translated in FR/EN/NL with "print" option