<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        #[\SensitiveParameter] string $token,
        protected ?User $user = null,
        protected ?string $resetUrl = null,
    )
    {
        parent::__construct($token);
    }

    public function toMail($notifiable): MailMessage
    {
        $userName = trim((string) ($notifiable->name ?? '')) ?: 'there';

        return (new MailMessage)
            ->subject('Reset your Grads Paths password')
            ->view('emails.reset-password', [
                'url' => $this->resetUrl ?: $this->resetUrl($notifiable),
                'userName' => $userName,
                'expiresIn' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        if (! $this->user) {
            return ['notification:reset-password'];
        }

        return array_filter([
            'notification:reset-password',
            'user:'.$this->user->getKey(),
            $this->user->email ? 'email:'.$this->user->email : null,
        ]);
    }
}
