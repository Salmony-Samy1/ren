<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Facility;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class FacilitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // الحصول على فئة العقارات (main_service_id = 4)
        $propertyCategory = Category::whereHas('mainService', function($query) {
            $query->where('name_en', 'Stays (Apartments & Chalets)');
        })->first();

        // إذا لم توجد فئة العقارات، استخدم أول فئة متاحة أو null
        $categoryId = $propertyCategory ? $propertyCategory->id : null;

        // قائمة المرافق الشاملة للعقارات
        $facilities = [
            // المرافق الأساسية
            ['name' => 'واي فاي مجاني', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'تكييف هواء', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'تدفئة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكيف هواء', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مروحة سقف', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق المنزلية
            ['name' => 'مطبخ مجهز', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ثلاجة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'فرن', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مايكروويف', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'غسالة أطباق', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خلاط', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'آلة قهوة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'محمصة خبز', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'عصارة فواكه', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مقلاة عميقة', 'category_id' => $categoryId, 'is_active' => true],
            
            // الغسيل والتنظيف
            ['name' => 'غسالة ملابس', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مجفف ملابس', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكواة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'طاولة كي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكان تعليق الملابس', 'category_id' => $categoryId, 'is_active' => true],
            
            // الترفيه والتلفزيون
            ['name' => 'تلفزيون', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'تلفزيون ذكي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شبكة نتفليكس', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شبكة أمازون برايم', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شبكة ديزني بلس', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'نظام صوتي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مشغل دي في دي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ألعاب فيديو', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق الخارجية
            ['name' => 'تراس', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شرفة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'حديقة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'فناء خارجي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'منطقة شواء', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شواية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مقاعد خارجية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مظلة', 'category_id' => $categoryId, 'is_active' => true],
            
            // المسابح والترفيه المائي
            ['name' => 'مسبح خاص', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مسبح مشترك', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'جاكوزي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'حوض ساخن', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ساونا', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'حمام بخار', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شاطئ خاص', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'منطقة ألعاب مائية', 'category_id' => $categoryId, 'is_active' => true],
            
            // الرياضة واللياقة
            ['name' => 'صالة ألعاب', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'معدات رياضية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'دراجة هوائية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'معدات يوغا', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ملعب تنس', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ملعب كرة قدم', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ملعب كرة سلة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ملعب بلياردو', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق الصحية والاستحمام
            ['name' => 'حمام خاص', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'حمام مشترك', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'دش خارجي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'منتجات استحمام', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مناشف', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مجفف شعر', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مشطاف', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مشطاف إلكتروني', 'category_id' => $categoryId, 'is_active' => true],
            
            // النوم والراحة
            ['name' => 'أغطية سرير', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'وسائد إضافية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'بطانيات إضافية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكتب عمل', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'كرسي مكتب', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'إضاءة مكتب', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مقعد أطفال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'سرير أطفال', 'category_id' => $categoryId, 'is_active' => true],
            
            // الأمان والأمن
            ['name' => 'حارس أمن', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'كاميرات مراقبة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'نظام إنذار', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'قفل ذكي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'صندوق آمن', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'بوابة أمنية', 'category_id' => $categoryId, 'is_active' => true],
            
            // المواصلات والتنقل
            ['name' => 'موقف سيارات', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'موقف سيارات مجاني', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'موقف سيارات مغطى', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'موقف سيارات تحت الأرض', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مصعد', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مصعد شحن', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'نقل من وإلى المطار', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة تاكسي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'تأجير سيارات', 'category_id' => $categoryId, 'is_active' => true],
            
            // الخدمات والضيافة
            ['name' => 'خدمة الغرف', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'غسيل وكي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'تنظيف يومي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'تنظيف أسبوعي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'استقبال 24/7', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة الكونسيرج', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة حمال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة طاهي خاص', 'category_id' => $categoryId, 'is_active' => true],
            
            // الطعام والشراب
            ['name' => 'مطعم', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مطعم داخلي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مطعم خارجي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'بار', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مقهى', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة إفطار', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة عشاء', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة وجبات خفيفة', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق التجارية
            ['name' => 'سوبر ماركت', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'متجر هدايا', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'صيدلية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'بنك', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'صراف آلي', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكتب بريد', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكتب سياحة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكتب تأجير', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق الطبية
            ['name' => 'عيادة طبية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'طوارئ طبية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'صيدلية 24/7', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة إسعاف', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مستشفى قريب', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق الترفيهية للأطفال
            ['name' => 'منطقة أطفال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ملعب أطفال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ألعاب أطفال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مربية أطفال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة رعاية أطفال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'كراسي أطفال', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مقاعد أطفال للسيارة', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق الخاصة
            ['name' => 'سبا', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مركز لياقة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مكتبة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'قاعة مؤتمرات', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'قاعة اجتماعات', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'قاعة أفراح', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مسرح', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'سينما', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق التقنية
            ['name' => 'شبكة إنترنت عالية السرعة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شبكة لاسلكية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'شحن سريع', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'محولات كهربائية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مولد كهرباء', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'نظام صوتي متقدم', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق البيئية
            ['name' => 'حديقة نباتية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'نافورة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'بركة ماء', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'منطقة استرخاء', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'مقاعد حديقة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'إضاءة خارجية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'نظام ري', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق الخاصة بالحيوانات الأليفة
            ['name' => 'مسموح بالحيوانات الأليفة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'منطقة لعب الحيوانات', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'خدمة رعاية الحيوانات', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'طعام الحيوانات', 'category_id' => $categoryId, 'is_active' => true],
            
            // المرافق الإضافية
            ['name' => 'مكتبة ألعاب', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ألعاب لوحية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ألعاب فيديو', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'طاولة بلياردو', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'طاولة تنس الطاولة', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'طاولة كرة القدم', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'آلة موسيقية', 'category_id' => $categoryId, 'is_active' => true],
            ['name' => 'ميكروفون', 'category_id' => $categoryId, 'is_active' => true],
        ];

        // إدراج المرافق في قاعدة البيانات
        foreach ($facilities as $facility) {
            Facility::firstOrCreate(
                ['name' => $facility['name']], // البحث بالاسم لتجنب التكرار
                $facility
            );
        }

        $this->command->info('تم إدراج ' . count($facilities) . ' مرفق في قاعدة البيانات بنجاح!');
    }
}
