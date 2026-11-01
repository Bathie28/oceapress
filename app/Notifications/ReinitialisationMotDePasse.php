<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ReinitialisationMotDePasse extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe OceaPress')
            ->view('emails.reinitialisation-mot-de-passe', ['url' => $url]);
    }
}
