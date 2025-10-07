<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Nationality;
use Illuminate\Http\Request;

class NationalityController extends Controller
{
    /**
     * الحصول على جميع الجنسيات النشطة
     * GET /api/nationalities
     */
    public function index(Request $request)
    {
        try {
            $nationalities = Nationality::getActiveOrdered();
            
            $data = $nationalities->map(function ($nationality) {
                return [
                    'id' => $nationality->id,
                    'name' => $nationality->name,
                    'name_en' => $nationality->name_en,
                    'code' => $nationality->code,
                ];
            });

            return format_response(true, 'تم جلب الجنسيات بنجاح', $data);
            
        } catch (\Exception $e) {
            return format_response(false, 'حدث خطأ في جلب الجنسيات', 500);
        }
    }

    /**
     * الحصول على جنسية محددة
     * GET /api/nationalities/{id}
     */
    public function show($id)
    {
        try {
            $nationality = Nationality::active()->findOrFail($id);
            
            $data = [
                'id' => $nationality->id,
                'name' => $nationality->name,
                'name_en' => $nationality->name_en,
                'code' => $nationality->code,
            ];

            return format_response(true, 'تم جلب الجنسية بنجاح', $data);
            
        } catch (\Exception $e) {
            return format_response(false, 'الجنسية غير موجودة', 404);
        }
    }

    /**
     * البحث في الجنسيات
     * GET /api/nationalities/search?q=سعودي
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return format_response(false, 'يرجى إدخال كلمة البحث', [], 400);
            }

            $nationalities = Nationality::active()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('name_en', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%");
                })
                ->ordered()
                ->get();

            $data = $nationalities->map(function ($nationality) {
                return [
                    'id' => $nationality->id,
                    'name' => $nationality->name,
                    'name_en' => $nationality->name_en,
                    'code' => $nationality->code,
                ];
            });

            return format_response(true, 'تم البحث في الجنسيات بنجاح', $data);
            
        } catch (\Exception $e) {
            return format_response(false, 'حدث خطأ في البحث', [], 500);
        }
    }
}
