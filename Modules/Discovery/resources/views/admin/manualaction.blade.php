@include('discovery::admin.partials.manual-actions.hub', [
  'adminManualActionsData' => $adminManualActionsData ?? app(\Modules\Discovery\app\Services\AdminManualActionsService::class)->build(),
])
