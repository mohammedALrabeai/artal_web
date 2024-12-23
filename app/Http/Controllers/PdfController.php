<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCPDF;

class PdfController extends Controller
{
    public function generatePdf()
    {
        require_once base_path('helpers/tcpdf_include.php');

        $pdf = new \TCPDF();
        $pdf->AddPage();
        
        // انسخ الكود الخاص بتوليد البيانات هنا
        
        $pdf->Output('employee_hiring_form.pdf', 'I');
    }

    public function generatePdf2($employee)
    {
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

        // إضافة بيانات الموظف
        $pdf->SetXY(40, 29.5);
        $pdf->Cell(0, 0, $employee->id, 0, 1);

        $pdf->SetXY(85, 29.5);
        $pdf->Cell(0, 0, $employee->national_id, 0, 1);

        $pdf->SetXY(140, 29.5);
        $pdf->Cell(0, 0, $employee->name, 0, 1);

        // يمكن إضافة المزيد من الحقول حسب الحاجة
        $pdf->SetXY(10, 46);
        $pdf->Cell(0, 0, $employee->iban, 0, 1);

        // إخراج PDF
        $pdf->Output('employee_hiring_form.pdf', 'I');
    }
}
