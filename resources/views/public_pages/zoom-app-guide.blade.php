@component('public_pages.partials.page-shell', ['title' => 'Zoom App Guide'])
  <h1>Zoom App Guide</h1>
  <p class="lede">This guide explains how Grads Paths uses Zoom to support virtual mentoring sessions and Office Hours.</p>

  <h2>What The Zoom Integration Does</h2>
  <p>Grads Paths uses Zoom to create virtual meeting links for confirmed bookings. Direct bookings receive a Zoom meeting for that booking. Office Hours sessions use one shared Zoom meeting for all students booked into the same Office Hours session.</p>

  <h2>Connecting Zoom As A Mentor</h2>
  <ol>
    <li>Sign in to your Grads Paths mentor account.</li>
    <li>Open mentor settings or availability settings.</li>
    <li>Choose the option to connect Zoom.</li>
    <li>Review the Zoom authorization screen and approve access.</li>
    <li>Return to Grads Paths. Once connected, students can book Zoom-based sessions with you.</li>
  </ol>

  <h2>Using Zoom For Bookings</h2>
  <p>When a student books a confirmed session, Grads Paths creates a Zoom meeting using the mentor’s connected Zoom account. Students see a Join Zoom Meeting link on the booking details page. Mentors see a Start Zoom Meeting link for hosted sessions.</p>

  <h2>Using Zoom For Office Hours</h2>
  <p>When the first student books an Office Hours session, Grads Paths creates one Zoom meeting for that session. Every additional student booked into the same Office Hours session uses the same shared Zoom link. If the first student changes the Office Hours focus, the Zoom meeting link stays the same.</p>

  <h2>Removing Zoom Access</h2>
  <p>Mentors can disconnect Zoom from Grads Paths account settings. You can also revoke app access from your Zoom account’s connected apps or marketplace settings. After Zoom is disconnected, students cannot book new Zoom meetings with that mentor until Zoom is reconnected.</p>

  <h2>Data Used By The Zoom Integration</h2>
  <p>Grads Paths stores Zoom authorization tokens for connected mentors, Zoom meeting identifiers, meeting links, meeting sync status, and limited meeting event information needed to operate booking and attendance workflows. See the <a href="{{ route('public.privacy') }}">Privacy Policy</a> for data rights and request instructions.</p>

  <h2>Support</h2>
  <p>For Zoom connection, booking, or meeting link issues, contact <a href="mailto:support@gradspaths.com">support@gradspaths.com</a> or visit the <a href="{{ route('public.support') }}">Support page</a>.</p>

  <p class="updated">Last updated: April 29, 2026</p>
@endcomponent
