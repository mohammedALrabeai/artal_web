<?php

namespace App\Services;

use TCPDF;
use App\Models\EmployeeProjectRecord;

class EmployeePdfService
{
    public function generatePdf(EmployeeProjectRecord $employeeProjectRecord)
    {
        // تحميل العلاقات مسبقاً
        $employeeProjectRecord->load(['project', 'zone', 'employee']);
        
        // التحقق من وجود البيانات
        if (!$employeeProjectRecord->project || !$employeeProjectRecord->zone) {
            throw new \Exception('Project or Zone data not found');
        }

        // إنشاء الكائن PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Company');
        $pdf->SetTitle('Employee Hiring and Onboarding Form');

        // إعداد الهوامش
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        // إعداد الخط
        $pdf->SetFont('dejavusans', '', 10);

        // إضافة صفحة
        $pdf->AddPage();
          // إضافة صورة كخلفية
          $backgroundImagePath = public_path('images/back.jpg'); // تأكد من وجود الصورة في هذا المسار
          if (file_exists($backgroundImagePath)) {
              $pdf->Image($backgroundImagePath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
          } else {
              error_log('Background image not found at: ' . $backgroundImagePath);
          }

       // Personal Information Section
$pdf->SetXY(40, 29.9); // Position for Employee Name
$pdf->Cell(0, 0, $employeeProjectRecord->employee->id, 0, 1);

$pdf->SetXY(85, 29.9); 
$pdf->Cell(0, 0, $employeeProjectRecord->employee->national_id, 0, 1);

$pdf->SetXY(140, 29.9); // Position for Employee ID
$pdf->Cell(0, 0, $employeeProjectRecord->employee->name(), 0, 1); 

$pdf->SetFont('aealarabiya', '', 12);


$pdf->SetXY(140, 35); // Position for Birth Date
$pdf->Cell(0, 0, $employeeProjectRecord->employee->birth_date, 0, 1);

$pdf->SetXY(84,35); 
$pdf->Cell(0, 0, $employeeProjectRecord->employee->contact_numbers, 0, 1);
$pdf->SetFont('aealarabiya', '', 10);

$pdf->SetXY(10, 35); // Position for Employee Name
$pdf->Cell(0, 0, $employeeProjectRecord->employee->email, 0, 1);





$pdf->SetXY(10, 41); // Position for Birth Date
$pdf->Cell(0, 0, $employeeProjectRecord->employee->address, 0, 1,'C');



$pdf->SetXY(120, 46); // Position for Birth Date
$pdf->Cell(0, 0, $employeeProjectRecord->employee->sponsor_company, 0, 1,'C');



$pdf->SetXY(10, 46); // Position for Employee Name
$pdf->Cell(0, 0, $employeeProjectRecord->employee->bank_account, 0, 1);

$pdf->SetXY(100, 78); // Position for Email
$pdf->Cell(0, 0, $employeeProjectRecord->project->name . ' - ' . $employeeProjectRecord->zone->name, 0, 1);





$pdf->SetXY(140, 120); // Position for Contract Type
$pdf->Cell(0, 0, "--", 0, 1);





$pdf->SetXY(140, 115); // Position for Contract Type
$pdf->Cell(0, 0, $employeeProjectRecord->start_date, 0, 1);




// $pdf->SetXY(140, 163); // Position for Onboarding Type
// $pdf->Cell(0, 0, $employee_name, 0, 1);

// // Approvals Section
// $pdf->SetXY(33, 204); // Position for Supervisor Name
// $pdf->Cell(0, 0, $supervisor_name, 0, 1);

// $pdf->SetXY(88, 204); // Position for Supervisor Name
// $pdf->Cell(0, 0, $supervisor_name, 0, 1);

// $pdf->SetXY(154, 204); // Position for Approver Name
// $pdf->Cell(0, 0, $approver_name, 0, 1);

        // إخراج PDF
        $pdf->Output('employee_hiring_form.pdf', 'I');
    }
}
