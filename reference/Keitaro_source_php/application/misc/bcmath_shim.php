<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

if (!extension_loaded("bcmath")) {
    function bcmulArray($arr, $scale)
    {
        $result = NULL;
        foreach ($arr as $n) {
            if ($result === NULL) {
                $result = $n;
            } else {
                $result = bcmul($result, $n, $scale);
            }
        }
        return $result;
    }
    function bcaddArray($arr, $scale)
    {
        $result = NULL;
        foreach ($arr as $n) {
            if ($result === NULL) {
                $result = $n;
            } else {
                $result = bcadd($result, $n, $scale);
            }
        }
        return $result;
    }
    function bcsubArray($arr, $scale)
    {
        $result = NULL;
        foreach ($arr as $n) {
            if ($result === NULL) {
                $result = $n;
            } else {
                $result = bcsub($result, $n, $scale);
            }
        }
        return $result;
    }
    $gIaBcMathShimSale = 0;
    function bcscale($scale = NULL)
    {
        if (!is_numeric($scale) || $scale < 0) {
            return false;
        }
        global $gIaBcSale;
        $gIaBcSale = $scale;
        return true;
    }
    function bcadd($a, $b, $scale = NULL)
    {
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        return round($a + $b, $scale);
    }
    function bcsub($a, $b, $scale = NULL)
    {
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        return round($a - $b, $scale === NULL ? bcscale() : $scale);
    }
    function bcdiv($a, $b, $scale = NULL)
    {
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        return round($a / $b, $scale === NULL ? bcscale() : $scale);
    }
    function bcmul($a, $b, $scale = NULL)
    {
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        return round($a * $b, $scale === NULL ? bcscale() : $scale);
    }
    function bcpow($a, $b, $scale = NULL)
    {
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        return round(pow($a, floor($b)), $scale === NULL ? bcscale() : $scale);
    }
    function bcpowmod($a, $b, $modulus, $scale = NULL)
    {
        if ($modulus == 0) {
            return NULL;
        }
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        $t = "0";
        while (bccomp($b, "0", $scale)) {
            if (bccomp(bcmod($b, "2"), "0", $scale)) {
                $t = bcmod(bcmul($t, $a, $scale), $modulus);
                $b = bcsub($b, "1", $scale);
            }
            $a = bcmod(bcmul($a, $a, $scale), $modulus);
            $b = bcdiv($b, "2", $scale);
        }
        return $t;
    }
    function bcmod($a, $modulus)
    {
        return $modulus == 0 ? NULL : $a % $modulus;
    }
    function bcsqrt($a, $scale = NULL)
    {
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        return round(sqrt($a), $scale === NULL ? bcscale() : $scale);
    }
    function bccomp($a, $b, $scale = NULL)
    {
        if ($scale === NULL) {
            global $gIaBcSale;
            $scale = $gIaBcSale;
        }
        $dif = round($a - $b, $scale);
        if ($dif < 0) {
            return 1;
        }
        if (0 < $dif) {
            return -1;
        }
        return 0;
    }
}

?>