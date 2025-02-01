<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;

class ApkController extends Controller
{
    /**
     * Handle APK file upload and store it.
     */
    public function installApk(Request $request)
    {
        $request->validate([
            'apk_file' => 'required|mimes:apk|max:50000', // Max file size 50MB
        ]);

        // Store the APK file
        $file = $request->file('apk_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = 'apk_files/' . $filename;

        Storage::disk('public')->put($filePath, File::get($file));

        $downloadUrl = url('/api/download-apk/' . $filename);

        return response()->json([
            'message' => 'APK uploaded successfully.',
            'download_url' => $downloadUrl
        ], 200);
    }

    /**
     * Serve the APK file for download.
     */
    public function downloadApk($filename)
    {
        // dd($filename);
        $filePath = storage_path("app/public/apks/" . $filename);

        if (!File::exists($filePath)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
