<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmailSettings extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        return $this->subject('اختبار إعدادات البريد')
                    ->markdown('emails.test-settings');
    }
}
