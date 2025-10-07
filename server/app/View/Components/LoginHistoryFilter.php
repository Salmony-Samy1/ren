<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LoginHistoryFilter extends Component
{
    public $search;
    public $loginStatusFilter;
    public $loginPlatformFilter;
    public $dateFrom;
    public $dateTo;

    /**
     * Create a new component instance.
     */
    public function __construct($search = '', $loginStatusFilter = '', $loginPlatformFilter = '', $dateFrom = '', $dateTo = '')
    {
        $this->search = $search;
        $this->loginStatusFilter = $loginStatusFilter;
        $this->loginPlatformFilter = $loginPlatformFilter;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.login-history-filter');
    }
}