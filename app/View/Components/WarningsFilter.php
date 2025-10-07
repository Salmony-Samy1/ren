<?php

namespace App\View\Components;

use Illuminate\View\Component;

class WarningsFilter extends Component
{
    public $search;
    public $warningTypeFilter;
    public $warningSeverityFilter;
    public $dateFrom;
    public $dateTo;

    /**
     * Create a new component instance.
     */
    public function __construct($search = '', $warningTypeFilter = '', $warningSeverityFilter = '', $dateFrom = '', $dateTo = '')
    {
        $this->search = $search;
        $this->warningTypeFilter = $warningTypeFilter;
        $this->warningSeverityFilter = $warningSeverityFilter;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.warnings-filter');
    }
}