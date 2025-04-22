<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmailSettings;

class TestEmailController extends Controller
{
    public function send()
    {
        try {
            Mail::to('mohammedalrabeai@gmail.com')->send(new TestEmailSettings());

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال البريد بنجاح إلى mohammedalrabeai@gmail.com'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إرسال البريد',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
