<?php

namespace App\Livewire\Customer;

use App\Models\Coupon;
use Livewire\Component;

class OfferComponent extends Component
{
    public function render()
    {
        $coupons = Coupon::active()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Coupon $coupon) => [
                'code'            => $coupon->code,
                'description'     => $coupon->description,
                'discount_label'  => $this->discountLabel($coupon),
                'min_order_taka'  => $coupon->min_order_taka,
                'valid_until'     => $coupon->valid_until ? $this->toBanglaDate($coupon->valid_until) : null,
            ]);

        return view('livewire.customer.offer-component', [
            'coupons' => $coupons,
        ])->layout('layouts.customer', [
                'title' => 'Offer | KhaiKhai', 'breadcrumbTitle' => 'Offer'
            ]);
    }

    /** Coupon type অনুযায়ী ছাড়ের লেখা বানানো */
    private function discountLabel(Coupon $coupon): string
    {
        return match ($coupon->type) {
            'percentage' => rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') . '% ছাড়'
                . ($coupon->max_discount ? ' (সর্বোচ্চ ৳' . $coupon->max_discount_taka . ')' : ''),
            'fixed_amount' => '৳' . number_format($coupon->value, 0) . ' ছাড়',
            'free_delivery' => 'ফ্রি ডেলিভারি',
            default => $coupon->description,
        };
    }

    /** তারিখকে বাংলা সংখ্যা ও মাসের নামে রূপান্তর */
    private function toBanglaDate(\Illuminate\Support\Carbon $date): string
    {
        $months = [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
            5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
            9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
        ];

        $digitMap = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

        $day  = strtr((string) $date->day, $digitMap);
        $year = strtr((string) $date->year, $digitMap);

        return "{$day} {$months[$date->month]} {$year}";
    }
}