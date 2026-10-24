<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $titre, public string $corps)
    {
    }

    public function build(): static
    {
        return $this->subject($this->titre)->view('emails.notification-client');
    }
}
