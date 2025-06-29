<?php 

// app/Mail/ReportMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

  public function build()
{
    return $this->subject('تقرير الموظفين غير المسندين')
        ->view('emails.simple-report')
        ->attach($this->filePath); // ← المسار المطلق يمر هنا
}

}
