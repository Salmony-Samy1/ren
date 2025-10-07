<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppRating;
use Illuminate\Support\Facades\Validator;

class AppRatingController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $user = auth()->user();
        $rating = AppRating::where('user_id', $user->id)->first();
        return response()->json([
            'success' => true,
            'data' => $rating
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $rating = AppRating::updateOrCreate(
            ['user_id' => $user->id],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'شكراً لتقييمك!',
            'data' => $rating
        ], 201);
    }
}
