<?php

namespace Modules\Discovery\app\Services;

use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class AdminManualActionsService
{
    public function build(): array
    {
        $institutionsCount = University::query()->count();

        $institutions = University::query()
            ->withCount('programs')
            ->orderByRaw('COALESCE(display_name, name)')
            ->limit(20)
            ->get([
                'id',
                'name',
                'display_name',
                'country',
                'city',
                'state_province',
                'is_active',
            ]);

        $services = ServiceConfig::query()
            ->orderBy('sort_order')
            ->orderBy('service_name')
            ->get([
                'id',
                'service_name',
                'duration_minutes',
                'is_active',
                'price_1on1',
                'price_1on3_per_person',
                'price_1on5_per_person',
                'is_office_hours',
                'office_hours_subscription_price',
                'credit_cost_1on1',
                'credit_cost_1on3',
                'credit_cost_1on5',
                'sort_order',
            ]);

        $mentors = Mentor::query()
            ->with([
                'user:id,name,email',
                'university:id,name,display_name',
                'services:id,service_name',
            ])
            ->orderBy('id')
            ->get();

        $users = User::query()
            ->with('credit:user_id,balance')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $programs = UniversityProgram::query()
            ->with('university:id,name,display_name')
            ->latest('id')
            ->get([
                'id',
                'university_id',
                'program_name',
                'program_type',
                'tier',
                'duration_months',
                'description',
                'is_active',
            ]);

        $feedback = Feedback::query()
            ->with([
                'student:id,name,email',
                'mentor.user:id,name,email',
                'mentor.university:id,name,display_name',
                'booking.service:id,service_name',
            ])
            ->latest('id')
            ->get();

        $bookings = Booking::query()
            ->with([
                'booker:id,name,email',
                'mentor.user:id,name,email',
                'service:id,service_name',
            ])
            ->latest('id')
            ->limit(100)
            ->get();

        return [
            'summary' => [
                'mentor_actions' => $mentors->count(),
                'credit_accounts' => $users->count(),
                'catalog_items' => $institutionsCount + $programs->count() + $services->count(),
                'feedback_items' => $feedback->count(),
                'booking_items' => $bookings->count(),
            ],
            'options' => [
                'mentor_statuses' => [
                    'pending' => 'Pending',
                    'active' => 'Active',
                    'paused' => 'Paused',
                    'rejected' => 'Rejected',
                ],
                'program_types' => [
                    'mba' => 'MBA',
                    'law' => 'Law',
                    'therapy' => 'Therapy',
                    'cmhc' => 'CMHC',
                    'mft' => 'MFT',
                    'msw' => 'MSW',
                    'clinical_psy' => 'Clinical Psychology',
                    'other' => 'Other',
                ],
                'program_tiers' => [
                    'elite' => 'Elite',
                    'top' => 'Top',
                    'regional' => 'Regional',
                ],
                'booking_outcomes' => [
                    'completed' => 'Completed',
                    'no_show_student' => 'Student no-show',
                    'no_show_mentor' => 'Mentor no-show',
                    'interrupted' => 'Interrupted',
                    'ended_early' => 'Ended early',
                    'unknown' => 'Unknown',
                ],
            ],
            'mentors' => $mentors->map(fn (Mentor $mentor) => [
                'id' => $mentor->id,
                'label' => trim(($mentor->user?->name ?: 'Unknown mentor').' #'.$mentor->id),
                'name' => $mentor->user?->name ?: 'Unknown mentor',
                'email' => $mentor->user?->email ?: '-',
                'status' => $mentor->status ?: 'pending',
                'type' => $mentor->title ?: ($mentor->mentor_type === 'professional' ? 'Professional Mentor' : 'Graduate Mentor'),
                'institution' => $mentor->university?->display_name ?: $mentor->university?->name ?: ($mentor->grad_school_display ?: '-'),
                'program_type' => $mentor->program_type ?: '-',
                'services' => $mentor->services->pluck('service_name')->values()->all(),
                'description' => $mentor->description ?: ($mentor->bio ?: 'No description added yet.'),
            ])->values()->all(),
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'label' => trim(($user->name ?: 'Unknown user').' ('.$user->email.')'),
                'name' => $user->name ?: 'Unknown user',
                'email' => $user->email,
                'credits' => (int) ($user->credit?->balance ?? 0),
            ])->values()->all(),
            'institutions' => $institutions->map(fn (University $institution) => [
                'id' => $institution->id,
                'label' => $institution->display_name ?: $institution->name,
                'name' => $institution->name,
                'display_name' => $institution->display_name,
                'country' => $institution->country ?: '-',
                'city' => $institution->city ?: '-',
                'state_province' => $institution->state_province ?: '-',
                'is_active' => (bool) $institution->is_active,
                'programs_count' => (int) $institution->programs_count,
            ])->values()->all(),
            'programs' => $programs->map(fn (UniversityProgram $program) => [
                'id' => $program->id,
                'label' => $program->program_name.' - '.($program->university?->display_name ?: $program->university?->name ?: 'Unknown university'),
                'name' => $program->program_name,
                'university' => $program->university?->display_name ?: $program->university?->name ?: '-',
                'program_type' => $program->program_type,
                'tier' => $program->tier,
                'duration_months' => $program->duration_months,
                'description' => $program->description ?: '-',
                'is_active' => (bool) $program->is_active,
            ])->values()->all(),
            'services' => $services->map(fn (ServiceConfig $service) => [
                'id' => $service->id,
                'label' => $service->service_name,
                'name' => $service->service_name,
                'duration_minutes' => (int) $service->duration_minutes,
                'is_active' => (bool) $service->is_active,
                'is_office_hours' => (bool) $service->is_office_hours,
                'price_1on1' => $service->price_1on1,
                'price_1on3_per_person' => $service->price_1on3_per_person,
                'price_1on5_per_person' => $service->price_1on5_per_person,
                'office_hours_subscription_price' => $service->office_hours_subscription_price,
                'credit_cost_1on1' => (int) $service->credit_cost_1on1,
                'credit_cost_1on3' => (int) $service->credit_cost_1on3,
                'credit_cost_1on5' => (int) $service->credit_cost_1on5,
                'sort_order' => (int) $service->sort_order,
            ])->values()->all(),
            'feedback' => $feedback->map(fn (Feedback $item) => [
                'id' => $item->id,
                'label' => '#'.$item->id.' - '.($item->mentor?->user?->name ?: 'Unknown mentor'),
                'student_name' => $item->student?->name ?: 'Unknown student',
                'mentor_name' => $item->mentor?->user?->name ?: 'Unknown mentor',
                'mentor_school' => $item->mentor?->university?->display_name ?: $item->mentor?->university?->name ?: '-',
                'service_name' => $item->booking?->service?->service_name ?: ($item->service_type ?: '-'),
                'stars' => (int) $item->stars,
                'comment' => $item->comment ?: '',
                'admin_note' => $item->admin_note ?: '',
                'is_visible' => (bool) $item->is_visible,
            ])->values()->all(),
            'bookings' => $bookings->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'label' => '#'.$booking->id.' - '.($booking->service?->service_name ?? 'Service').' - '.($booking->booker?->name ?? 'Booker'),
                'status' => $booking->status,
                'session_outcome' => $booking->session_outcome ?: 'completed',
                'completion_source' => $booking->completion_source ?: 'schedule',
                'booker_name' => $booking->booker?->name ?? 'Booker',
                'mentor_name' => $booking->mentor?->user?->name ?? 'Mentor',
                'service_name' => $booking->service?->service_name ?? 'Service',
                'session_at' => $booking->session_at?->toIso8601String(),
                'session_outcome_note' => $booking->session_outcome_note ?? '',
            ])->values()->all(),
        ];
    }
}
