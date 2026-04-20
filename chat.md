# Booking Chat System Plan

## Goal
- Add real-time chat between the mentor and the primary student for each booking.
- Persist every message in the database first, then broadcast it live through Laravel Reverb.

## Current State
- The `chats` table already exists and is linked to `bookings`.
- Student and mentor booking pages already show a demo chat panel.
- There is no real chat model, controller, event, authorization rule, or frontend realtime wiring yet.

## Architecture
- Database is the source of truth for all chat history.
- Laravel Reverb is the realtime delivery layer.
- Each booking uses a private broadcast channel: `booking.{bookingId}`.
- Message flow:
  - client sends message to backend
  - backend validates booking access and saves the message
  - backend broadcasts the saved message to the private booking channel
  - subscribed student and mentor clients receive the message instantly

## Participants and Rules
- V1 supports only the mentor and the primary booking student.
- This applies to `1on1`, `1on3`, and `1on5`.
- Guest participants do not access chat in V1.
- Chat is available as soon as the booking exists.
- Completed bookings remain visible and their chat history remains readable.

## Backend Changes
- Add `Modules/Bookings/app/Models/Chat.php`.
- Add booking chat endpoints for:
  - loading a booking thread
  - sending a message on a booking thread
- Add authorization checks based on booking ownership:
  - student must be the booking owner
  - mentor must be the mentor assigned to the booking
- Add a `ChatMessageSent` broadcast event.
- Register a private booking channel in `routes/channels.php`.
- Add a `Booking::chats()` relationship.

## Frontend Changes
- Replace fake chat bubbles in `public/assets/js/demo9.js`.
- Load the selected booking thread from the backend.
- Subscribe to the selected booking’s private Reverb channel.
- Leave the previous booking channel when the user switches bookings.
- Send chat messages with a normal POST request.
- Append persisted messages immediately and render live incoming messages from Reverb.

## Reverb Setup
- Use Laravel broadcasting with Reverb as the default broadcaster.
- Required env values:
  - `BROADCAST_CONNECTION`
  - `REVERB_APP_ID`
  - `REVERB_APP_KEY`
  - `REVERB_APP_SECRET`
  - `REVERB_HOST`
  - `REVERB_PORT`
  - `REVERB_SCHEME`
- Run `php artisan reverb:start` alongside the normal app server.
- Run the queue worker as usual for queued features already in the app.
- Frontend uses the Pusher-compatible client to connect to Reverb and authenticate private channels through `/broadcasting/auth`.

## Files to Change
- `Modules/Bookings/...`
- `public/assets/js/demo9.js`
- `routes/channels.php`
- broadcasting / Reverb config and booking page payloads

## Testing
- Student can load and send chat messages for their own booking.
- Mentor can load and send chat messages for their own booking.
- Unrelated student cannot load or send.
- Unrelated mentor cannot load or send.
- `1on3` and `1on5` still allow only mentor + primary student chat in V1.
- Loading a thread marks received unread messages as read.
- Sending a message broadcasts on `booking.{id}`.
- Student and mentor booking pages load the correct thread when the selected booking changes.

## Out of Scope
- Attachments
- Typing indicators
- Guest participant chat access
- Presence / online status
- Emoji reactions

## Rollout Notes
- Reverb server and Laravel app must both be running for live delivery.
- If realtime is temporarily unavailable, persisted chat history should still load from the database.
