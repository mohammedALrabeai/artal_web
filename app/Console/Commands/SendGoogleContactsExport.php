<?php

namespace App\Console\Commands;

use App\Exports\EmployeesGoogleContactsExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class SendGoogleContactsExport extends Command
{
    protected $signature = 'employees:export-contacts';

    protected $description = 'Export employees to Google Contacts CSV and email it';

public function handle()
{
    $export = new \App\Exports\EmployeesGoogleContactsExport;

    // 🔄 توليد CSV كبيانات مباشرة في الذاكرة
    $csv = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::CSV);

    // 📧 إرسال الايميل بالمرفق فقط دون setBody
    \Illuminate\Support\Facades\Mail::raw('مرفق ملف جهات اتصال الموظفين بصيغة CSV جاهزة للاستيراد في Google Contacts.', function ($message) use ($csv) {
        $message->to('mohammedalrabeai@gmail.com')
                ->subject('📇 جهات اتصال الموظفين')
                ->attachData($csv, 'contacts.csv', [
                    'mime' => 'text/csv',
                ]);
    });

    $this->info('✅ تم إرسال الملف بنجاح إلى بريدك.');
}

}

// . php artisan employees:export-contacts
