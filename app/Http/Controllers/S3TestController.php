<?php
namespace App\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Http\Request;

class S3TestController extends Controller
{
    public function testS3()
    {
        $s3 = new S3Client([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        try {
            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => 'uploads/test-file.txt',
                'Body' => 'This is a test file',
                'ACL' => 'public-read',
            ]);

            return response()->json([
                'message' => 'File uploaded successfully!',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'File upload failed!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
