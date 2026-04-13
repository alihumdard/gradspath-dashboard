<?php

namespace Modules\Auth\app\Models;

use App\Models\User as BaseUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\CreditTransaction;
use Modules\Payments\app\Models\UserCredit;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Models\StudentProfile;
use Modules\Settings\app\Models\UserSetting;
use Modules\Support\app\Models\SupportTicket;

class User extends BaseUser
{
    protected string $guard_name = 'web';

    public function getMorphClass(): string
    {
        return BaseUser::class;
    }

    public function mentor(): HasOne
    {
        return $this->hasOne(Mentor::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function credit(): HasOne
    {
        return $this->hasOne(UserCredit::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'student_id');
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
