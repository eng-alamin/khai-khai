<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Slider;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sliders = [
            ['image' => 'sliders/slider-1.jpg', 'url' => '/products?section=special_offer'],
            ['image' => 'sliders/slider-2.jpg', 'url' => '/products?section=trending'],
            ['image' => 'sliders/slider-3.jpg', 'url' => '/products?section=best_seller'],
            ['image' => 'sliders/slider-4.jpg', 'url' => '/restaurants'],
            ['image' => 'sliders/slider-5.jpg', 'url' => null],
        ];

        foreach ($sliders as $slider) {
            Slider::updateOrCreate(
                ['image' => $slider['image']],
                [
                    'url'       => $slider['url'],
                    'is_active' => true,
                ]
            );
        }
    }
}
