<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function generatePdf()
    {
        require_once base_path('app/Http/Controllers/pdf/tcpdf_include.php');

        $pdf = new \TCPDF();
        $pdf->AddPage();
        
        // انسخ الكود الخاص بتوليد البيانات هنا
        
        $pdf->Output('employee_hiring_form.pdf', 'I');
    }
}
