<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(protected ?User $user = null)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $userName = trim((string) ($notifiable->name ?? '')) ?: 'there';

        return (new MailMessage)
            ->subject('Verify your email address')
            ->view('emails.verify-email', [
                'url' => $this->verificationUrl($notifiable),
                'userName' => $userName,
                'expiresIn' => config('auth.verification.expire', 60),
            ]);
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
