<?php

namespace App\Helpers;

class NumberFormatter
{
    public static function formatCount($number) {
        if ($number < 1000) {
            return $number;
        }
        
        $units = ['', 'k', 'M', 'B', 'T'];
        $power = floor(log($number, 1000));
        $unit = $units[$power];
        $formattedNumber = $number / pow(1000, $power);
        
        $formattedNumber = round($formattedNumber, 1);
        $formattedNumber = rtrim(rtrim($formattedNumber, '0'), '.');
        
        return $formattedNumber . $unit;
    }
}