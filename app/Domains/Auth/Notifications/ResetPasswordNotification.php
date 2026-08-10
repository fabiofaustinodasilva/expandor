<?php

namespace App\Domains\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * E-mail transacional de redefinição — platform-managed (Expandor).
 * Sem senha no corpo; link com token; expiração via config/auth.php.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $name = trim((string) ($notifiable->name ?? ''));
        $greeting = $name !== '' ? 'Olá, '.$name.'.' : 'Olá.';

        return (new MailMessage)
            ->subject('Redefina sua senha no Expandor')
            ->greeting($greeting)
            ->line('Recebemos uma solicitação para redefinir sua senha no Expandor.')
            ->action('Redefinir senha', $this->resetUrl($notifiable))
            ->line('Este link expira em '.$expire.' minutos.')
            ->line('Se você não solicitou essa alteração, ignore este e-mail.')
            ->salutation('Expandor');
    }
}
