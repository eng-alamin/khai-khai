<?php

// app/Livewire/Vendor/LiveOrderCount.php

namespace App\Livewire\Vendor;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Poll;
use Livewire\Component;

class LiveOrderCount extends Component
{
    #[Poll(10000)] // প্রতি ১০ সেকেন্ডে refresh
    public int $count = 0;

    public function mount(): void
    {
        $this->count = $this->getCount();
    }

    public function render()
    {
        $this->count = $this->getCount();
        return view('livewire.vendor.live-order-count');
    }

    private function getCount(): int
    {
        return Order::where('restaurant_id', Auth::user()->restaurant->id)
            ->whereIn('status', ['pending', 'confirmed', 'preparing'])
            ->count();
    }
}