# Stripe Implementation Checklist

Source plan: [docs/docx.plan.md](/home/rauf/projects/gradspath-dashboard/docs/docx.plan.md)

This file extracts the Stripe-specific requirements from the backend plan and maps them to the current implementation state.

Status legend:

- `[x]` Implemented or mostly working
- `[-]` Partially implemented
- `[ ]` Not implemented yet

## Scope Covered

- Student credit purchases
- Paid booking checkout
- Stripe webhooks
- Mentor Stripe Connect onboarding
- Mentor payout enablement
- Mentor earnings distribution

## Business Model From The Plan

- Platform charges students
- Mentors onboard with Stripe Connect to receive payouts
- Office Hours pricing example in the plan:
  - Student pays `$200`
  - Mentor earns `$15` per attendee
  - Platform keeps the remainder

This is a `marketplace/platform payout` model, not a direct “mentor charges the student” model.

## Current Stripe Status

### 1. Student Credit Purchase

- `[x]` Stripe Checkout session creation for credit purchases
- `[x]` Credit purchase success handling through Stripe webhook flow
- `[x]` Credit balance increment after successful payment

Notes:

- The code already creates Stripe Checkout sessions for credit packs.
- `checkout.session.completed` is part of the current webhook handling logic.

## 2. Paid Booking Checkout

- `[x]` Stripe Checkout session creation for paid bookings
- `[x]` Booking finalization after successful payment webhook
- `[x]` Clear separation between credit-based Office Hours bookings and cash-paid service bookings across all user flows

Notes:

- Platform-side payment collection is implemented.
- Meeting/payment ordering in the plan is broadly aligned with the current booking payment flow.

## 3. Webhook Handling

- `[x]` Stripe webhook endpoint exists
- `[x]` Signature verification exists
- `[x]` `checkout.session.completed` is processed
- `[x]` `account.updated` is processed
- `[-]` Local development depends on queue worker being active
- `[ ]` Production-facing webhook event documentation/runbook

Required production events for the current code:

- `checkout.session.completed`
- `account.updated`

Notes:

- Connect events must be configured correctly in production so `account.updated` from connected accounts reaches the webhook endpoint.

## 4. Mentor Stripe Connect Onboarding

- `[x]` “Enable Payouts” flow exists
- `[x]` Connected account creation exists
- `[x]` Hosted onboarding/account link creation exists
- `[x]` Mentor record stores `stripe_account_id`
- `[x]` Mentor status flags store `payouts_enabled`
- `[x]` Mentor status flags store `stripe_onboarding_complete`
- `[x]` Mentor can reopen payout onboarding/update flow

Notes:

- Current implementation uses legacy Connect account creation with `type=express`.
- Stripe’s current best-practice direction for new builds is Accounts v2 with controller properties.

## 5. Mentor Payout Enablement Sync

- `[x]` `account.updated` webhook updates mentor payout status
- `[x]` Webhook data is persisted locally
- `[x]` Queue-backed processing works once worker is running
- `[-]` UI messaging distinguishes “returned from Stripe” from “fully enabled”

Notes:

- The success toast after returning from Stripe means onboarding was opened/returned, not necessarily that payouts are enabled.
- Final enablement depends on Stripe fields like `payouts_enabled`, `details_submitted`, and outstanding requirements.

## 6. Mentor Earnings Distribution

- `[x]` Local payout ledger for mentor earnings
- `[x]` Platform fee / mentor share calculation persistence
- `[x]` Stripe transfer implementation for completed paid bookings
- `[x]` Automatic transfer attempt to mentor connected accounts after booking completion
- `[x]` Retry path for eligible or failed mentor transfers
- `[x]` Reversal of pending payout rows when a paid booking is cancelled before completion
- `[x]` Admin visibility into payout amounts and payout state

Notes:

- The current implementation uses `separate charges and transfers`: students pay the platform, then the platform creates Stripe Transfers to mentor connected accounts.
- A mentor payout row is created when a paid booking is finalized from Stripe checkout.
- Payout rows stay `pending_release` until the booking is completed.
- If the mentor has a connected account and `payouts_enabled`, completion attempts a Stripe Transfer.
- If mentor onboarding is incomplete, the payout moves to `ready` and can be retried after onboarding completes.
- Failed transfer attempts are stored with a failure reason and retried by the scheduled `payments:retry-mentor-payouts` command.
- Service/session split rules are now editable from admin pricing and stored on `services_config`.
- `config/payments.php` no longer contains service payout fallbacks; paid service splits must be configured in admin/database.
- Office Hours creates a mentor payout per completed attendee booking using the service's admin-managed per-attendee amount, seeded at `$15`.

## 7. Refunds And Credits

- `[x]` Credit deduction logic exists
- `[x]` Credit refund logic exists for cancellation flows
- `[x]` Stripe cash refund flow for paid booking cancellations
- `[x]` Stripe transfer reversal before refund when mentor funds were already transferred
- `[x]` Local booking refund ledger tracks credit refunds, Stripe refunds, transfer reversals, and failures

Notes:

- Eligible cancellations automatically refund the full credit amount or full Stripe payment amount.
- If a mentor transfer already exists, the app attempts the Stripe transfer reversal before refunding the student.
- If Stripe refund or transfer reversal fails, the booking moves to `cancelled_pending_refund` for admin follow-up.

## 8. Data The Plan Expects

- `[x]` `mentors.stripe_account_id`
- `[x]` `mentors.payouts_enabled`
- `[x]` `mentors.stripe_onboarding_complete`
- `[x]` webhook event records
- `[x]` credit balance records
- `[x]` booking payment records
- `[x]` mentor payout/transfer records
- `[x]` platform fee accounting records

## Recommended Next Stripe Work

1. Production-check the final mentor payout policy:
   - admin-managed service/session splits are the only live payout source
   - Office Hours per-attendee mentor payout is admin-managed and seeded at `$15`
2. Improve mentor settings UI copy:
   - `Onboarding opened`
   - `Pending Stripe review`
   - `Payouts enabled`
3. Document production webhook setup:
   - platform events
   - connected account events
   - queue worker requirement
   - scheduled retry command requirement
4. Review whether to keep legacy Express account creation or migrate future Connect work toward Stripe Accounts v2/controller properties.

## Minimal Production Webhook Setup

- Platform event: `checkout.session.completed`
- Connect event: `account.updated`

## Architecture Note

The current codebase is consistent with a platform-marketplace model:

- students pay the platform
- mentors onboard with Stripe Connect
- mentor status sync is implemented
- mentor fund distribution is implemented through Stripe Transfers after completed paid bookings



Remianing

Big Remaining Items

Office Hours automation

Rotation engine is still the main missing piece.
Need automatic weekly session/service rotation.
Office Hours payout rule is implemented from admin pricing: mentor gets the configured per-attendee amount after completion.
Refunds

Credit refund method exists, but cancellation flow still looks support/manual-oriented.
Paid booking Stripe cash refunds are not implemented.
Need define what happens if booking is cancelled after mentor transfer.
Support Tickets

Ticket creation/admin reply exist.
Still missing user/mentor “my ticket history”.
Still missing rate limiting.
Threaded email follow-up workflow is not implemented.
Feedback / Reviews

Feedback submission and admin moderation exist.
Still missing:
most-mentioned tag/keyword analytics,
mentor reply to reviews,
stronger public filtering/sorting,
realtime stats propagation.
Settings / Profile

Basic student/mentor profile updates exist.
Stripe Connect now works.
Still remaining:
avatar/image upload with cleanup,
Slack/community integration,
clearer payout status UI copy,
optional Zoom account/provider settings if mentors need personal Zoom connection.
Booking Management

Booking, Zoom links/events, chat, paid checkout, and payout release are mostly working.
Still missing:
reschedule flow,
stricter chat availability window if you want 24-48 hour access only,
admin booking management screens may need polish/confirmation.
Mentor Notes

This is now more implemented than the checklist says.
Mentor can create/update notes for hosted bookings and access is protected.
Still missing:
admin soft delete,
edit/delete UI beyond update-on-same-booking,
search/filter backend,
strict decision: should mentors see notes from all mentors or only their own? Current browser test shows notes from multiple mentors.
Authentication

Core auth works.
Remaining from plan:
mentor password approval workflow using admin-given password,
professional users exempt from .edu rule if still required.
