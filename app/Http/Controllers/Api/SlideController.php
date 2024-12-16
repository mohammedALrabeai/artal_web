<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;

class SlideController extends Controller
{
    /**
     * إرجاع قائمة بالسلايدات النشطة.
     */
    public function getActiveSlides(): JsonResponse
    {
        $slides = Slide::where('is_active', true)->get(['id', 'title', 'description', 'image_url']);

        return response()->json([
            'status' => 'success',
            'data' => $slides,
        ]);
    }
}
