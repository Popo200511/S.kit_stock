<?php

return [
    // Storefront branding + contact channels — no cart/checkout, so "buying"
    // just means the customer taps one of these to reach a human.
    'name' => env('SHOP_NAME', 'ส.กิจการค้า'),
    'phone' => env('SHOP_PHONE'),
    'line_id' => env('SHOP_LINE_ID'),
    'line_url' => env('SHOP_LINE_URL'),
];
