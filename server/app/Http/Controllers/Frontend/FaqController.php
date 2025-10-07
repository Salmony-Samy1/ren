<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

class FaqController extends Controller
{

    public function index()
    {
        $faqs = Faq::where('is_visible', true)->orderBy('sort_order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $faqs
        ]);
    }

    public function show(Faq $faq)
    {
        return response()->json([
            'success' => true,
            'data' => $faq
        ]);
    }
}
