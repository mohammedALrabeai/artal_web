<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class FileUploadController2 extends Controller
{
    // عرض النموذج
    public function showForm()
    {
        return view('upload');
    }

    // معالجة رفع الملف
    public function uploadFile(Request $request)
    {
        // تحقق من صحة الإدخال
        $request->validate([
            'file' => 'required|file|max:5120', // الحد الأقصى 5 ميجا بايت
        ]);

        try {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName(); // اسم الملف الأصلي
            $key = "uploads/{$filename}";

            // تتبع معلومات الملف
            Log::info('Attempting to upload file to S3', [
                'filename' => $filename,
                'key' => $key,
                'size' => $file->getSize(),
            ]);

            // رفع الملف إلى S3
            $result = Storage::disk('s3')->put($key, file_get_contents($file), 'public');

            // تحقق من نجاح العملية
            if (!$result) {
                Log::error('S3 upload failed', ['key' => $key]);
                return back()->withErrors(['file' => 'Failed to upload file to S3.']);
            }

            // الحصول على رابط الملف
            $url = Storage::disk('s3')->url($key);

            Log::info('File uploaded successfully', [
                'key' => $key,
                'url' => $url,
            ]);

            return back()->with('success', 'File uploaded successfully!')->with('file_url', $url);
        } catch (\Exception $e) {
            Log::error('File upload error', ['message' => $e->getMessage()]);
            return back()->withErrors(['file' => 'File upload failed: ' . $e->getMessage()]);
        }
    }
}
