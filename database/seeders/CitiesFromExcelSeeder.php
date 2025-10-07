<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Country;
use App\Models\City;
use App\Models\Region;
use Illuminate\Support\Facades\File;

class CitiesFromExcelSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure countries exist
        $sa = Country::firstOrCreate([], []);
        // Translate
        if (method_exists($sa, 'translateOrNew')) {
            $sa->translateOrNew('ar')->name = 'المملكة العربية السعودية';
            $sa->translateOrNew('en')->name = 'Saudi Arabia';
            $sa->save();
        }

        $bh = Country::firstOrCreate([], []);
        if (method_exists($bh, 'translateOrNew')) {
            $bh->translateOrNew('ar')->name = 'البحرين';
            $bh->translateOrNew('en')->name = 'Bahrain';
            $bh->save();
        }

        $root = base_path();
        $saFile = $root . DIRECTORY_SEPARATOR . 'جميع_محافظات_السعودية_مع_نيوم.xlsx';
        $bhFile = $root . DIRECTORY_SEPARATOR . 'Bahrain_Governorates_Cities.xlsx';

        if (File::exists($saFile)) {
            $this->importSaudi($saFile, $sa);
        }
        if (File::exists($bhFile)) {
            $this->importBahrain($bhFile, $bh);
        }
    }

    protected function importSaudi(string $path, Country $country): void
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        if (!$rows || count($rows) < 2) return;

        $headers = $this->normalizeHeaders(array_shift($rows));
        $regionKey = $this->findHeader($headers, ['region','المـنـطـقـة','المنطقة','المحافظة','محافظة','emarah','governorate']);
        $cityKey = $this->findHeader($headers, ['city','المدينة','اسم المدينة','name','اسم']);

        foreach ($rows as $row) {
            $regionName = $this->val($row, $regionKey);
            $cityName = $this->val($row, $cityKey);
            if (!$regionName && !$cityName) continue;

            // Create city first
            $city = City::firstOrCreate(['country_id' => $country->id, 'is_active' => true]);
            if (method_exists($city, 'translateOrNew')) {
                $city->translateOrNew('ar')->name = $cityName ?: $regionName;
                $city->translateOrNew('en')->name = $cityName ?: $regionName;
                $city->save();
            }

            // Create region under city
            if ($regionName) {
                $region = Region::firstOrCreate(['city_id' => $city->id, 'is_active' => true]);
                if (method_exists($region, 'translateOrNew')) {
                    $region->translateOrNew('ar')->name = $regionName;
                    $region->translateOrNew('en')->name = $regionName;
                    $region->save();
                }
            }
        }
    }

    protected function importBahrain(string $path, Country $country): void
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        if (!$rows || count($rows) < 2) return;

        $headers = $this->normalizeHeaders(array_shift($rows));
        $govKey = $this->findHeader($headers, ['governorate','المحافظة','المحافظات']);
        $cityKey = $this->findHeader($headers, ['city','المدينة','اسم المدينة','name','اسم']);

        foreach ($rows as $row) {
            $govName = $this->val($row, $govKey);
            $cityName = $this->val($row, $cityKey);
            if (!$govName && !$cityName) continue;

            // city per governorate (or per city if provided)
            $city = City::firstOrCreate(['country_id' => $country->id, 'is_active' => true]);
            if (method_exists($city, 'translateOrNew')) {
                $city->translateOrNew('ar')->name = $cityName ?: $govName;
                $city->translateOrNew('en')->name = $cityName ?: $govName;
                $city->save();
            }

            if ($govName) {
                $region = Region::firstOrCreate(['city_id' => $city->id, 'is_active' => true]);
                if (method_exists($region, 'translateOrNew')) {
                    $region->translateOrNew('ar')->name = $govName;
                    $region->translateOrNew('en')->name = $govName;
                    $region->save();
                }
            }
        }
    }

    protected function normalizeHeaders(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $k => $v) {
            $headers[$k] = Str::lower(trim((string)$v));
        }
        return $headers;
    }

    protected function findHeader(array $headers, array $candidates): ?string
    {
        foreach ($headers as $k => $h) {
            foreach ($candidates as $cand) {
                if (Str::contains($h, Str::lower($cand))) {
                    return $k;
                }
            }
        }
        return null;
    }

    protected function val(array $row, ?string $key): ?string
    {
        if ($key === null) return null;
        $v = $row[$key] ?? null;
        $v = is_string($v) ? trim($v) : $v;
        return $v ?: null;
    }
}

