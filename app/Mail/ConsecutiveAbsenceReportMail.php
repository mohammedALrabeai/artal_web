<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ConsecutiveAbsenceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function build()
    {
        return $this->subject('تقرير الغياب المتتالي للموظفين')
            ->markdown('emails.consecutive-absence')
            ->attach(Storage::path($this->filePath));
    }
}
