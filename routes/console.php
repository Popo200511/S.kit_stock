<?php

use App\Jobs\FetchShopeeOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// No-ops internally until a Shopee shop is actually connected (see FetchShopeeOrders::handle).
Schedule::job(new FetchShopeeOrders)->everyFifteenMinutes()->name('shopee-order-sync')->withoutOverlapping();
