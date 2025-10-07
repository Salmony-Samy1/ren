<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPropertyLocation extends Command
{
    protected $signature = 'properties:backfill-location {--dry-run : Do not write, only show} {--limit=1000}';
    protected $description = 'Backfill properties.city_id/region_id/neigbourhood_id from related services when missing.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Backfilling property location fields (dry-run=' . ($dry ? 'yes' : 'no') . ', limit=' . $limit . ')');

        $q = Property::query()
            ->with('service')
            ->where(function($q){
                $q->whereNull('city_id')->orWhereNull('region_id')->orWhereNull('neigbourhood_id');
            })
            ->orderBy('id')
            ->limit($limit);

        $count = 0;
        foreach ($q->get() as $prop) {
            $svc = $prop->service;
            if (!$svc) { continue; }
            $update = [];
            if (empty($prop->city_id) && !empty($svc->city_id)) { $update['city_id'] = $svc->city_id; }
            // If you later add service.region_id or service.neigbourhood_id, you can copy similarly

            if (!empty($update)) {
                $count++;
                if ($dry) {
                    $this->line("Would update property #{$prop->id}: " . json_encode($update));
                } else {
                    DB::table('properties')->where('id', $prop->id)->update($update);
                    $this->line("Updated property #{$prop->id}: " . json_encode($update));
                }
            }
        }

        $this->info('Done. Updated ' . $count . ' properties.');
        return 0;
    }
}

