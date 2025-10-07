<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Http\Resources\ActivityCollection;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Repositories\ActivityRepo\IActivityRepo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ActivitiesController extends Controller
{
    public function __construct(private IActivityRepo $repo)
    {
    }

    public function index()
    {
        $activities = $this->repo->getAll(paginated: true, filter: ['user_id' => auth()->id()], relations: ['images', 'neigbourhood']);
        if ($activities) {
            return new ActivityCollection($activities);
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function store(ActivityRequest $request)
    {
        DB::beginTransaction();
        $activity = $this->repo->create($request->merge(['user_id' => auth()->id()])->all());
        if ($activity) {
            foreach ($request->images as $image) {
                $path = $image->move(public_path('images'), $image->getClientOriginalName());
                $activity->images()->create(['path' => $path]);
            }
            DB::commit();
            return new ActivityResource($activity);
        }
        DB::rollBack();
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function show(Activity $activity)
    {
        $activity = $activity->load('images', 'neigbourhood');
        return new ActivityResource($activity);
    }

    public function update(ActivityRequest $request, Activity $activity)
    {
        DB::beginTransaction();
        $activity = $this->repo->update($activity->id, $request->all());
        if ($activity) {
            foreach ($activity->images as $image) {
                if (File::exists($image->path)) {
                    File::delete($image->path);
                }
                $image->delete();
            }
            $activity->images()->delete();
            foreach ($request->images as $image) {
                $path = $image->move(public_path('images'), $image->getClientOriginalName());
                $activity->images()->create(['path' => $path]);
            }
            DB::commit();
            return new ActivityResource($activity);
        }
        DB::rollBack();
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function destroy(Activity $activity)
    {
        $result = $this->repo->delete($activity->id);
        if ($result) {
            return format_response(true, __('activity deleted successfully'));
        }
        return format_response(false, __('something went wrong'), code: 500);
    }
}
