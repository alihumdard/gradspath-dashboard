<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class QueuedPasswordResetCode extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        protected ?User $user = null,
        public readonly int $expiresIn = 30,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $userName = trim((string) ($notifiable->name ?? '')) ?: 'there';

        return (new MailMessage)
            ->subject('Reset your Grads Paths password')
            ->view('emails.verify-email', [
                'code' => $this->code,
                'userName' => $userName,
                'expiresIn' => $this->expiresIn,
                'emailTitle' => 'Reset your Grads Paths password',
                'heading' => 'Reset your password',
                'subtitle' => 'Use the following verification code to reset your Grads Paths account password.',
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        if (! $this->user) {
            return ['notification:password-reset-code'];
        }

        return array_filter([
            'notification:password-reset-code',
            'user:'.$this->user->getKey(),
            $this->user->email ? 'email:'.$this->user->email : null,
        ]);
    }
}
