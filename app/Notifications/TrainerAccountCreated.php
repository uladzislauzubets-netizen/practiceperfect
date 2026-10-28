<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainerAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $businessName,
        private readonly ?string $temporaryPassword,
        private readonly ?string $setupUrl,
    ) {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your '.config('app.name').' trainer account is ready')
            ->greeting('Welcome, '.$notifiable->first_name.'!')
            ->line("A trainer account for {$this->businessName} has been created for you.");

        if ($this->temporaryPassword !== null) {
            return $message
                ->line('Sign in with your email address and this temporary password:')
                ->line($this->temporaryPassword)
                ->line('You will be asked to choose a new password the first time you sign in.')
                ->action('Sign in', route('login'));
        }

        return $message
            ->line('Use the button below to choose your password and finish setting up your account.')
            ->action('Set up your account', (string) $this->setupUrl)
            ->line('This link expires in '.config('auth.setup_link_expire_days').' days.');
    }
}
