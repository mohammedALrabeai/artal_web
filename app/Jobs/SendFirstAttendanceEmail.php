<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Zone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendFirstAttendanceEmail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;

    protected $zone;

    protected $attendanceDate;

    /**
     * Create a new job instance.
     */
    public function __construct(Employee $employee, Zone $zone, $attendanceDate)
    {
        $this->employee = $employee;
        $this->zone = $zone;
        $this->attendanceDate = $attendanceDate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // قائمة الإيميلات التي سيتم الإرسال لها
        $recipients = [
            'legal@artalgroup.net',
            'admin2@artalgroup.net',
            'sultan@artalgroup.net',
            'hradmin@artalgroup.net',
            'mohammedalrabeai@gmail.com',
            'legal2@artalgroup.net',
            'hr@artalgroup.net',
            'hradmin2@artalgroup.net',
            'hr2@artalgroup.net',
            // 'anotheremail@example.com',
            // 'thirdemail@example.com',
            // يمكنك إضافة المزيد هنا...
        ];

        // إذا لم توجد إيميلات، لا ترسل شيء
        if (empty($recipients)) {
            return;
        }

        $subject = 'إشعار مباشرة موظف جديد';

        // تجهيز بيانات الموظف بأمان
        $fullName = $this->employee->name() ?? 'غير متوفر';
        $employeeId = $this->employee->id ?? 'غير متوفر';
        $nationalId = $this->employee->national_id ?? 'غير متوفر';
        $mobileNumber = $this->employee->mobile_number ?? 'غير متوفر';
        $zoneName = $this->zone->name ?? 'غير محدد';
        $attendanceDate = $this->attendanceDate ?? 'غير متوفر';

        // نص الرسالة HTML
        $message = "
        <html dir='rtl' lang='ar'>
        <body style='font-family: Tahoma, Arial, sans-serif; background-color: #f9f9f9; padding: 20px;'>
            <div style='background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
                <h2 style='color: #333333;'>📢 إشعار مباشرة | {$fullName} | {$nationalId}</h2>
                <p style='font-size: 16px; color: #555555;'>نحيطكم علمًا بأن الموظف التالي قد باشر عمله:</p>
                <table style='border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 20px;'>
                    <tr style='background-color: #f1f1f1;'>
                        <td style='border: 1px solid #ddd; padding: 8px;'>الاسم الكامل</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>{$fullName}</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>الرقم الوظيفي</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>{$employeeId}</td>
                    </tr>
                    <tr style='background-color: #f1f1f1;'>
                        <td style='border: 1px solid #ddd; padding: 8px;'>رقم الهوية</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>{$nationalId}</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>رقم الجوال</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>{$mobileNumber}</td>
                    </tr>
                    <tr style='background-color: #f1f1f1;'>
                        <td style='border: 1px solid #ddd; padding: 8px;'>الموقع المباشر فيه</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>{$zoneName}</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>تاريخ المباشرة</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>{$attendanceDate}</td>
                    </tr>
                </table>
                <p style='margin-top: 20px; font-size: 16px; color: #555555;'>نتمنى له التوفيق والنجاح في مهام عمله.</p>
                <p style='color: #999999; font-size: 14px;'>مع أطيب التحيات،<br>Artal Solutions Team</p>
            </div>
        </body>
        </html>
        ";

        // إرسال الإيميل
        Mail::send([], [], function ($mail) use ($recipients, $subject, $message) {
            $mail->to('mohammed.artalgroup@gmail.com') // 🔥 هنا ضع إيميل حقيقي خاص بك أو noreply
                ->bcc($recipients)               // الإيميلات الحقيقية يتم إرسالها هنا
                ->subject($subject)
                ->html($message);
        });
    }
}
