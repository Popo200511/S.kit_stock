<?php

namespace App\Support;

use Carbon\Carbon;

class ThaiTime
{
    /**
     * Short Thai relative-time string ("13 ชั่วโมง", "2 วัน") — the app locale
     * stays 'en' for everything else, so Carbon's own diffForHumans() won't do.
     */
    public static function diffForHumans(Carbon $time): string
    {
        $seconds = (int) max(0, $time->diffInSeconds(now()));

        if ($seconds < 60) {
            return 'เมื่อสักครู่';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return "{$minutes} นาที";
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return "{$hours} ชั่วโมง";
        }

        $days = intdiv($hours, 24);
        if ($days < 7) {
            return "{$days} วัน";
        }

        $weeks = intdiv($days, 7);
        if ($weeks < 5) {
            return "{$weeks} สัปดาห์";
        }

        $months = intdiv($days, 30);
        if ($months < 12) {
            return "{$months} เดือน";
        }

        return intdiv($days, 365).' ปี';
    }
}
