<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEnglishNames extends Command
{
    /**
     * The name and signature of the console command.
     * Usage: php artisan employees:import-english-names english_names.xlsx
     * (place the file under storage/app/english_names.xlsx)
     *
     * @var string
     */
    protected $signature = 'employees:import-english-names {file : اسم ملف Excel داخل storage/app (مثال: english_names.xlsx)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import English names for employees from given Excel file in storage/app';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // نحصل على اسم الملف من الوسيط
        $fileName = $this->argument('file');
        // نحدد المسار الكامل ضمن storage/app
        $filePath = storage_path('app/' . $fileName);

        if (! file_exists($filePath)) {
            $this->error("الملف غير موجود في storage/app: {$fileName}");
            return 1;
        }

        $this->info("جاري تحميل الملف: {$filePath}");
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $highestRow  = $sheet->getHighestRow();

        DB::beginTransaction();
        try {
            // عمود A يحتوي على ID في الصف 1 و B يحتوي على Name
            for ($row = 2; $row <= $highestRow; $row++) {
                $empId   = trim((string) $sheet->getCell('A' . $row)->getValue());
                $engName = trim((string) $sheet->getCell('B' . $row)->getValue());

                if ($empId !== '' && $engName !== '') {
                    DB::table('employees')
                        ->where('id', $empId)
                        ->update(['english_name' => $engName]);
                }
            }

            DB::commit();
            $this->info('تم استيراد جميع الأسماء الإنجليزية بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('حدث خطأ أثناء الاستيراد: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
