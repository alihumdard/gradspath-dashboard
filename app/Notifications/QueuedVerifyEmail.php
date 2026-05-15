<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(protected ?User $user = null)
    {
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        if (! $this->user) {
            return ['notification:verify-email'];
        }

        return array_filter([
            'notification:verify-email',
            'user:'.$this->user->getKey(),
            $this->user->email ? 'email:'.$this->user->email : null,
        ]);
    }
}
