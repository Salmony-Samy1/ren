<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ServiceUsageFilter extends Component
{
    public $search;
    public $serviceStatusFilter;
    public $serviceTypeFilter;
    public $dateFrom;
    public $dateTo;

    /**
     * Create a new component instance.
     */
    public function __construct($search = '', $serviceStatusFilter = '', $serviceTypeFilter = '', $dateFrom = '', $dateTo = '')
    {
        $this->search = $search;
        $this->serviceStatusFilter = $serviceStatusFilter;
        $this->serviceTypeFilter = $serviceTypeFilter;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.service-usage-filter');
    }
}