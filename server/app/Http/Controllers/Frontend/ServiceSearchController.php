<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceSearchController extends Controller
{
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $keyword = $request->input('keyword');
        $categoryId = $request->input('category_id');

        $query = Service::with(['category','user','event','catering.items'])->where('is_approved', true);

        if ($keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $services = $query->paginate(10);
        $services->setCollection($services->getCollection()->map(fn($s) => new \App\Http\Resources\ServiceResource($s)));

        return response()->json([
            'success' => true,
            'message' => 'Services fetched successfully.',
            'data' => $services
        ]);
    }
}
