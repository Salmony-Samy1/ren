<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ActivitiesFilter extends Component
{
    public $search;
    public $activityActionFilter;
    public $activityStatusFilter;
    public $dateFrom;
    public $dateTo;

    /**
     * Create a new component instance.
     */
    public function __construct($search = '', $activityActionFilter = '', $activityStatusFilter = '', $dateFrom = '', $dateTo = '')
    {
        $this->search = $search;
        $this->activityActionFilter = $activityActionFilter;
        $this->activityStatusFilter = $activityStatusFilter;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.activities-filter');
    }
}