<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class About extends Component
{
    public function render()
    {
        return view('livewire.storefront.about')->layout('components.layouts.storefront', [
            'title' => 'เกี่ยวกับเรา · '.config('shop.name'),
            'description' => 'รู้จัก '.config('shop.name').' ร้านอาหารสัตว์และของใช้คู่ฟาร์ม พร้อมดูแลลูกค้าทุกท่าน',
            'canonical' => route('shop.about'),
        ]);
    }
}
