<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class QueuedVerifyEmail extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        protected ?User $user = null,
        public readonly int $expiresIn = 15,
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
            ->subject('Verify your Grads Paths Account')
            ->view('emails.verify-email', [
                'code' => $this->code,
                'userName' => $userName,
                'expiresIn' => $this->expiresIn,
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
