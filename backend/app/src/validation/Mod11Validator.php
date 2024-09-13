<?php

namespace SilverStripe\Validation;

class Mod11Validator
{
    public static function Mod11DaiValidator(string $value): bool {
        //First validate on regex
        $daiRegex = '^[0-9]{8,9}[0-9X]$';
        if (!preg_match("/$daiRegex/", $value)){
            return false;
        }

        $values = str_split($value);
        $checkDigit = array_pop($values);
        $digits = array_map('intval', $values);

        $sum = 0;
        $digits = array_reverse($digits);
        foreach ($digits as $i => $v) {
            $mod = 2 + ($i % 8);
            $sum += $mod * $v;
        }

        $rem = $sum % 11;
        $check = 0;
        if ($rem !== 0) {
            if ($rem === 1) {
                $check = 'X';
            } else {
                $check = 11 - $rem;
            }
        }

        return (string) $check === $checkDigit;
    }


    public static function Mod11IsniOrcidValidator(string $value): bool {
        // Remove all non-digits except 'X'
        $value = preg_replace('/[^0-9X]/', '', $value);
        $values = str_split($value);

        $checkDigit = array_pop($values);
        $digits = array_map('intval', $values);

        $sum = 0;
        foreach ($digits as $v) {
            $sum = ($sum + $v) * 2;
        }

        $remainder = $sum % 11;
        $result = (12 - $remainder) % 11;

        if ($result === 10) {
            $result = 'X';
        }

        return (string) $result === $checkDigit;
    }
}