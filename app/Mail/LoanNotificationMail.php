<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class LoanNotificationMail extends Mailable
{
    public $loan;

    public function __construct($loan)
    {
        $this->loan = $loan;
    }

    public function build()
    {
        return $this->subject('Employee Resignation Notification')
                    ->view('emails.loan-notification')
                    ->with('loan', $this->loan);
    }
}

