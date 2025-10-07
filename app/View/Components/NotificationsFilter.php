<?php

namespace App\View\Components;

use Illuminate\View\Component;

class NotificationsFilter extends Component
{
    public $search;
    public $notificationStatusFilter;
    public $notificationActionFilter;
    public $dateFrom;
    public $dateTo;

    /**
     * Create a new component instance.
     */
    public function __construct($search = '', $notificationStatusFilter = '', $notificationActionFilter = '', $dateFrom = '', $dateTo = '')
    {
        $this->search = $search;
        $this->notificationStatusFilter = $notificationStatusFilter;
        $this->notificationActionFilter = $notificationActionFilter;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.notifications-filter');
    }
}