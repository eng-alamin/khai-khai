<?php

namespace App\Livewire\Vendor;

use Livewire\Component;

class DashboardComponent extends Component
{
    public function render()
    {
        return view('livewire.vendor.dashboard-component')
            ->layout('layouts.vendor', ['title' => 'Dashboard | KhaiKhai', 'breadcrumbTitle' => 'Dashboard']);
            
    }
}
