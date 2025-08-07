<?php

namespace App\Imports;

namespace App\Imports;

use Carbon\Carbon;
use App\Models\Employee;
use GeniusTS\HijriDate\Hijri;
use App\Models\CommercialRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use GeniusTS\HijriDate\Date as HijriDate;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;

class EmployeesImport implements ToCollection
{

    private $useIdsFromFile;
    private $addedBy = null;

    public function __construct($useIdsFromFile = false, $addedBy = null)
    {
        $this->useIdsFromFile = $useIdsFromFile;
        $this->addedBy = $addedBy;
        // dd($this->addedBy);

    }
    protected $columnMap = [
        'id' => 'id',
        'الاسم' => 'full_name',
        'NAME' => 'english_name', // إضافة العمود الإنجليزي
        'الجنسية' => 'nationality',
        'رقم الهوية' => 'national_id',
        'تاريخ انتهاء الهوية' => 'national_id_expiry',
        'تاريخ الميلاد (ميلادي)' => 'birth_date',
        'فصيلة الدم' => 'blood_type',
        'الجنس' => 'gender',
        'الحالة الاجتماعية' => 'marital_status',
        'الأساسي' => 'basic_salary',
        'سكن' => 'living_allowance',
        'آخرى' => 'other_allowances',
        'الوظيفة' => 'job_title',
        'الأيبان' => 'bank_account',
        'الحالة' => 'job_status',
        'اسم البنك' => 'bank_name',
        'التأمين الطبي' => 'health_insurance_status',
        'اسم شركة التأمين' => 'health_insurance_company',
        'نهاية تاريخ التأمين' => 'insurance_end_date',
        'الجوال' => 'mobile_number',
        'جوال إضافي (طواريء)' => 'phone_number',
        'البريد الإلكتروني' => 'email',
        'اسم الموقع المرشح' => 'preferred_zone_name',


        'المباشرة' => 'actual_start',
        'تاريخ الإضافة' => 'contract_start',
        'المؤهل' => 'qualification',
        'مكان الميلاد' => 'birth_place',
        'شركة' => 'company_name', // العمود الذي يحتوي على اسم الشركة
        'رقم المشترك' => 'insurance_number', // العمود الذي يحتوي على رقم المشترك
    ];

    public function collection(Collection $rows)
    {
        $header = $rows->shift(); // قراءة أول صف لمعرفة الأعمدة
        $errors = []; // لتخزين الأخطاء

        foreach ($rows as $rowIndex => $row) {
            $employeeData = [];

            foreach ($this->columnMap as $excelColumn => $modelField) {
                $columnIndex = $header->search($excelColumn);
                $value = $columnIndex !== false ? $row[$columnIndex] : null;

                // استخدام المعرف من الملف أو توليد المعرف تلقائيًا
                if ($modelField === 'id') {
                    if ($this->useIdsFromFile) {
                        $employeeData['id'] = $value; // استخدام القيمة من الملف
                    } else {
                        $employeeData['id'] = null; // توليد المعرف تلقائيًا
                    }
                    continue;
                }
                // معالجة القيم الخاصة
                if ($modelField === 'national_id') {
                    $value = (string) $value; // تحويل إلى نص
                } elseif ($modelField === 'birth_date') {
                    $value = $this->parseGregorianDate($value); // تحويل التاريخ الميلادي إلى التنسيق الصحيح
                    if ($value == null) {
                        $value = '1990-01-01';
                    }
                } elseif ($modelField === 'actual_start') {
                    $value = $this->parseGregorianDate($value); // تحويل تاريخ المباشرة إلى التنسيق الصحيح
                } elseif ($modelField === 'contract_start') {
                    $value = $this->parseGregorianDate($value); // تحويل تاريخ المباشرة إلى التنسيق الصحيح
                } elseif ($modelField === 'national_id_expiry') {
                    $value = $this->convertHijriToGregorian($value); // تحويل التاريخ الهجري إلى الميلادي
                } elseif ($modelField === 'insurance_end_date') {
                    $value = null; // تعيين القيمة إلى NULL دائمًا
                } elseif ($modelField === 'nationality') {
                    if ($value === null) {
                        $value = '-'; // تحويل الجنسية إلى الإنجليزية
                    }
                } elseif ($modelField === 'basic_salary') {

                    if ($value == null) {
                        $value = 0;
                    } else {
                        // التحقق من قيمة basic_salary وجعلها رقمًا فقط
                        $value = is_numeric($value) ? (float) $value : 0;
                    }
                } elseif ($modelField === 'bank_account') {
                    if ($value == null) {
                        $value = '-';
                    }
                } elseif ($modelField === 'email') {
                    $value = trim($value); // إزالة المسافات الزائدة
                } elseif ($modelField === 'mobile_number') {
                    $value = $this->formatSaudiMobileNumber((string)$value); // تحويل إلى نص
                }

                $employeeData[$modelField] = $value;
            }

            // ✅ معالجة حقل "company_name" لتحديد نوع التأمين والسجل
            $originalCompanyName = $employeeData['company_name'] ?? null;

            if (!$originalCompanyName || str_contains($originalCompanyName, 'بدون')) {
                // إذا كان الحقل فارغ أو يحتوي "بدون" → لا تأمين
                $employeeData['insurance_type'] = $originalCompanyName ? '' : 'commercial_record';
                $employeeData['commercial_record_id'] = null;
                \Log::info('Company name is empty or marked as بدون → insurance_type: ' . $employeeData['insurance_type']);
            } else {
                // محاولة استخراج رقم السجل التجاري من النص
                preg_match('/\d{6,}/', $originalCompanyName, $matches);
                $recordNumber = $matches[0] ?? null;

                if ($recordNumber) {
                    $company = CommercialRecord::where('insurance_number', $recordNumber)->first();
                    $employeeData['commercial_record_id'] = $company?->id;
                    $employeeData['insurance_type'] = 'commercial_record';
                    \Log::info('Company number found: ' . $employeeData['commercial_record_id']);
                } else {
                    $employeeData['commercial_record_id'] = null;
                    $employeeData['insurance_type'] = 'commercial_record'; // حتى لو لم نجد الرقم
                    \Log::info('No valid record number found in company_name');
                }
            }

            // إزالة حقل `company_name` بعد استخدامه
            unset($employeeData['company_name']);

            // إضافة `insurance_number`
            $employeeData['insurance_number'] = $employeeData['insurance_number'] ?? null;


            // تعيين region و city من مكان الميلاد
            $birthPlace = $employeeData['birth_place'] ?? "-";
            $employeeData['region'] = $birthPlace;
            $employeeData['city'] = $birthPlace;

            // إضافة القيم الافتراضية
            $employeeData['job_status'] = $employeeData['job_status'] ?? 'نشط'; // تعيين قيمة افتراضية
            $employeeData['qualification'] = $employeeData['qualification'] ?? 'غير محدد';
            $employeeData['specialization'] = $employeeData['qualification'];
            $employeeData['preferred_zone_name'] = $employeeData['preferred_zone_name'] ?? ''; // تعيين قيمة افتراضية

            // تعيين كلمة مرور افتراضية مشفرة
            $employeeData['password'] = '12345678';

            // تقسيم الاسم إلى أجزاء
            $fullName = $employeeData['full_name'] ?? null;

            if ($fullName) {

                // تقسيم الاسم بناءً على المسافات
                $nameParts = explode(' ', trim($fullName));


                // تعيين الاسم الأول (المقطع الأول)
                $employeeData['first_name'] = $nameParts[0] ?? null;

                // تعيين اسم الأب (المقطع الثاني)
                $employeeData['father_name'] = $nameParts[1] ?? null;

                // تعيين اسم العائلة (المقطع الأخير)
                $employeeData['family_name'] = $nameParts[count($nameParts) - 1] ?? null;

                // تعيين اسم الجد (باقي المقاطع بين الاسم الأول والعائلة)
                $employeeData['grandfather_name'] = count($nameParts) > 3
                    ? implode(' ', array_slice($nameParts, 2, -1)) // جمع المقاطع بين الثاني والأخير
                    : (isset($nameParts[2]) ? $nameParts[2] : null); // إذا كان هناك مقطع ثالث فقط
            }

            // التحقق من صحة البيانات
            $validator = Validator::make($employeeData, [
                'first_name' => 'required|string|max:255',
                'national_id' => 'required|string|unique:employees,national_id',
                // 'email' => 'nullable|email|unique:employees,email',
                'email' => 'nullable',
                'birth_date' => 'nullable|date',
                'national_id_expiry' => 'nullable|date',
                'mobile_number' => 'nullable|string|max:20',
                'bank_account' => 'nullable|string|max:50',
                'job_status' => 'required|string|max:255',
                'qualification' => 'nullable|string|max:255',
                'specialization' => 'nullable|string|max:255',
                'region' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'errors' => $validator->errors()->all(),
                ];
                continue; // تخطي الصف في حال وجود أخطاء
            }
            $employeeData['added_by'] = $this->addedBy;

            unset($employeeData['full_name']); // إزالة الحقل المؤقت
            // إذا كان التوليد التلقائي للمعرفات مفعّلًا
            //    if ($this->generateIds) {
            //     unset($employeeData['id']); // إزالة الحقل من البيانات
            // }
            Employee::create($employeeData); // حفظ البيانات
        }

        // تسجيل الأخطاء في السجل
        if (!empty($errors)) {
            dd($errors);
            foreach ($errors as $error) {
                Log::error("Row {$error['row']} Errors: ", $error['errors']);
            }
        }

        // إشعار المستخدم
        if (empty($errors)) {
            Notification::make()
                ->title("تم استيراد الموظفين بنجاح!")
                ->body("تم استيراد جميع الموظفين بنجاح.")
                ->success();
        } else {

            Notification::make()
                ->title("تم استيراد بعض الموظفين مع وجود أخطاء!")
                ->body("تم استيراد بعض الموظفين مع وجود أخطاء. تحقق من السجل (log)!")
                ->warning();
            // Filament::notify('warning', 'تم استيراد بعض الموظفين مع وجود أخطاء. تحقق من السجل (log)!');
        }
    }

    private function parseGregorianDate($date)
    {
        try {
            // تنظيف القيمة وإزالة المسافات
            $cleanedDate = trim($date);

            // التحقق إذا كانت القيمة رقمية (Excel Date Format)
            if (is_numeric($cleanedDate)) {
                // Excel يستخدم 1 يناير 1900 كبداية
                return Carbon::create(1900, 1, 1)->addDays($cleanedDate - 2)->format('Y-m-d');
            }

            // التحقق من التنسيق المتوقع باستخدام Regex
            if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $cleanedDate)) {
                return Carbon::createFromFormat('Y/m/d', $cleanedDate)->format('Y-m-d');
            } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $cleanedDate)) {
                return Carbon::createFromFormat('d/m/Y', $cleanedDate)->format('Y-m-d');
            }

            // التحقق من التنسيق YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanedDate)) {
                return Carbon::createFromFormat('Y-m-d', $cleanedDate)->format('Y-m-d');
            }

            // التحقق من التنسيق DD-MM-YYYY
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $cleanedDate)) {
                return Carbon::createFromFormat('d-m-Y', $cleanedDate)->format('Y-m-d');
            }

            // التحقق من التنسيق D-MM-YYYY (بدون صفر في اليوم)
            if (preg_match('/^\d{1}-\d{2}-\d{4}$/', $cleanedDate)) {
                // إضافة صفر إلى اليوم ليصبح DD-MM-YYYY
                $parts = explode('-', $cleanedDate);
                $formattedDate = str_pad($parts[0], 2, '0', STR_PAD_LEFT) . '-' . $parts[1] . '-' . $parts[2];
                return Carbon::createFromFormat('d-m-Y', $formattedDate)->format('Y-m-d');
            }
            // إذا لم يكن الرقم أو التاريخ صالحًا، اطرح استثناء
            throw new \Exception("Invalid date format: $cleanedDate");
        } catch (\Exception $e) {
            if ($date == null) {
                return null;
            }
            if ($date == '') {
                return null;
            }
            if ($date == 'بدون تامينات') {
                return null;
            }
            if ($date == 'انتظار المباشرة') {
                return null;
            }
            dd($e);
            // إذا فشل التحويل، قم بإرجاع NULL أو قيمة افتراضية
            return null;
        }
    }


    private function formatSaudiMobileNumber($number)
    {
        // تنظيف الرقم من أي مسافات أو رموز غير رقمية
        $cleanedNumber = preg_replace('/\D/', '', trim($number));

        // إذا كان الرقم يحتوي على رمز الدولة مسبقًا (+966 أو 00966)، قم بإزالته
        if (preg_match('/^(?:\+966|00966)/', $cleanedNumber)) {
            $cleanedNumber = preg_replace('/^(?:\+966|00966)/', '', $cleanedNumber);
        }

        // إذا كان الرقم يبدأ بـ "05"، احذف الصفر الأول
        if (preg_match('/^05\d{8}$/', $cleanedNumber)) {
            $cleanedNumber = substr($cleanedNumber, 1);
        }

        // إذا كان الرقم يبدأ بـ "5" مباشرة، فهو بالفعل في الصيغة الصحيحة
        if (preg_match('/^5\d{8}$/', $cleanedNumber)) {
            return '966' . $cleanedNumber;
        }

        // إذا لم يكن الرقم بصيغة صحيحة، قم بإرجاع NULL أو رسالة خطأ
        return null;
    }


    private function convertHijriToGregorian($hijriDate)
    {
        try {
            // التأكد من أن التاريخ موجود وليس فارغاً
            if (!$hijriDate) {
                return now()->addYears(1)->format('Y-m-d'); // قيمة افتراضية إذا كان التاريخ غير موجود
            }

            // تحويل الأرقام العربية إلى إنجليزية
            $hijriDateEnglish = $this->convertArabicToEnglish($hijriDate);

            // استخدام مكتبة HijriDate لتحويل التاريخ
            $date = HijriDate::parseFromFormat('Y/m/d', $hijriDateEnglish); // تحويل النص إلى تاريخ هجري
            return $date->toGregorian()->format('Y-m-d'); // تحويل إلى الميلادي وتنسيقه
        } catch (\Exception $e) {
            // تعيين قيمة افتراضية إذا فشل التحويل
            return now()->addYears(1)->format('Y-m-d');
        }
    }

    private function convertArabicToEnglish($string)
    {
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($arabicNumbers, $englishNumbers, $string);
    }
}
