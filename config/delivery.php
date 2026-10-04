<?php

/*
|--------------------------------------------------------------------------
| Delivery & restaurant lists
|--------------------------------------------------------------------------
| Single source of truth for the fixed lists used by several screens
| (registration, vendor settings, admin forms). Every screen offers - and
| accepts - exactly the same values.
*/

return [

    // Zones a rider can be placed in.
    'zones' => [
        'Dhaka-Metro',
        'Dhaka-North',
        'Dhaka-South',
        'Gazipur',
        'Narayanganj',
        'Chittagong',
        'Sylhet',
        'Rajshahi',
        'Khulna',
        'Barisal',
        'Rangpur',
        'Mymensingh',
    ],

    // Which restaurant cities each rider zone covers (zone-based dispatch).
    // Every value in 'zones' must appear here, and every value must be a
    // city from 'cities' below.
    'zone_cities' => [
        'Dhaka-Metro'  => ['Dhaka'],
        'Dhaka-North'  => ['Dhaka'],
        'Dhaka-South'  => ['Dhaka'],
        'Gazipur'      => ['Gazipur'],
        'Narayanganj'  => ['Narayanganj'],
        'Chittagong'   => ['Chittagong'],
        'Sylhet'       => ['Sylhet'],
        'Rajshahi'     => ['Rajshahi'],
        'Khulna'       => ['Khulna'],
        'Barisal'      => ['Barisal'],
        'Rangpur'      => ['Rangpur'],
        'Mymensingh'   => ['Mymensingh'],
    ],

    // Restaurant categories (restaurants.category).
    'categories' => [
        'Bangla Food',
        'Fast Food',
        'Burger',
        'Pizza',
        'Biryani',
        'Chinese',
        'Indian',
        'Italian',
        'Seafood',
        'Dessert & Sweets',
        'Tea & Coffee',
        'Beverages',
        'Healthy Food',
        'Street Food',
        'Other',
    ],

    // Cities a restaurant can be in (restaurants.city).
    'cities' => [
        'Dhaka',
        'Gazipur',
        'Narayanganj',
        'Chittagong',
        'Sylhet',
        'Rajshahi',
        'Khulna',
        'Barisal',
        'Rangpur',
        'Mymensingh',
    ],

];