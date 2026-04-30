@php
  $manualSection = old('manual_section', session('manual_section', 'mentor'));
  $manualData = $adminManualActionsData ?? app(\Modules\Discovery\app\Services\AdminManualActionsService::class)->build();
  $manualSummary = $manualData['summary'] ?? [];
@endphp

<script id="adminManualActionsData" type="application/json">@json($manualData)</script>

<section class="manual-hub" id="manualActionsApp" data-initial-section="{{ $manualSection }}">
  <div class="manual-hub__hero">
    <div>
      <p class="manual-hub__eyebrow">Admin Operations</p>
      <h2 class="manual-hub__title">Manual Actions Hub</h2>
      <p class="manual-hub__subtitle">Run controlled admin actions with one shared workflow: review the current record, submit the change, and keep the audit trail intact.</p>
    </div>

    <div class="manual-hub__stats" aria-label="Manual action summary">
      <div class="manual-hub__stat">
        <strong>{{ number_format((int) ($manualSummary['mentor_actions'] ?? 0)) }}</strong>
        <span>mentor accounts</span>
      </div>
      <div class="manual-hub__stat">
        <strong>{{ number_format((int) ($manualSummary['credit_accounts'] ?? 0)) }}</strong>
        <span>credit accounts</span>
      </div>
      <div class="manual-hub__stat">
        <strong>{{ number_format((int) ($manualSummary['catalog_items'] ?? 0)) }}</strong>
        <span>catalog records</span>
      </div>
      <div class="manual-hub__stat">
        <strong>{{ number_format((int) ($manualSummary['feedback_items'] ?? 0)) }}</strong>
        <span>feedback items</span>
      </div>
    </div>
  </div>

  <div class="manual-hub__nav">
    <button class="manual-nav-btn" type="button" data-section-target="mentor">Mentor</button>
    <button class="manual-nav-btn" type="button" data-section-target="credits">Credits</button>
    <button class="manual-nav-btn" type="button" data-section-target="institutions">Institutions</button>
    <button class="manual-nav-btn" type="button" data-section-target="programs">Programs</button>
    <button class="manual-nav-btn" type="button" data-section-target="services">Services</button>
    <button class="manual-nav-btn" type="button" data-section-target="pricing">Pricing</button>
    <button class="manual-nav-btn" type="button" data-section-target="feedback">Feedback</button>
    <button class="manual-nav-btn" type="button" data-section-target="bookings">Bookings</button>
  </div>

  <div class="manual-group-grid">
    @include('discovery::admin.partials.manual-actions.account-actions', ['adminManualActionsData' => $manualData])
    @include('discovery::admin.partials.manual-actions.catalog-actions', ['adminManualActionsData' => $manualData])
    @include('discovery::admin.partials.manual-actions.moderation-actions', ['adminManualActionsData' => $manualData])
  </div>
</section>
