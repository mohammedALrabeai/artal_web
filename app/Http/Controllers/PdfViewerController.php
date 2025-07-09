<?php

namespace App\Http\Controllers;

use App\Models\PdfDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PdfViewerController extends Controller
{
    public function show(PdfDocument $pdfDocument): View
    {
        $pdfDocument->load('textFields');
        
        return view('pdf.viewer', compact('pdfDocument'));
    }

    public function saveFieldData(Request $request, PdfDocument $pdfDocument): JsonResponse
    {
        $request->validate([
            'field_data' => 'required|array',
        ]);

        // يمكنك حفظ البيانات في قاعدة البيانات أو في session
        session(['pdf_field_data_' . $pdfDocument->id => $request->field_data]);

        return response()->json(['success' => true]);
    }

    public function generatePrintablePdf(Request $request, PdfDocument $pdfDocument)
    {
        $fieldData = $request->input('field_data', []);
        
        // هنا يمكنك استخدام مكتبة مثل TCPDF أو DomPDF لإنشاء PDF جديد مع النصوص
        // للبساطة، سنعيد البيانات كـ JSON
        
        return response()->json([
            'success' => true,
            'field_data' => $fieldData,
            'pdf_url' => $pdfDocument->file_url
        ]);
    }
}

