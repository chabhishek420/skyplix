<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Vendor;

class SxGeo
{
    protected $fh = NULL;
    protected $ip1c = NULL;
    protected $info = NULL;
    protected $range = NULL;
    protected $db_begin = NULL;
    protected $b_idx_str = NULL;
    protected $m_idx_str = NULL;
    protected $b_idx_arr = NULL;
    protected $m_idx_arr = NULL;
    protected $m_idx_len = NULL;
    protected $db_items = NULL;
    protected $country_size = NULL;
    protected $db = NULL;
    protected $regions_db = NULL;
    protected $cities_db = NULL;
    public $id2iso = ["", "AP", "EU", "AD", "AE", "AF", "AG", "AI", "AL", "AM", "CW", "AO", "AQ", "AR", "AS", "AT", "AU", "AW", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BM", "BN", "BO", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA", "CC", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CU", "CV", "CX", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG", "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM", "FO", "FR", "SX", "GA", "GB", "GD", "GE", "GF", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT", "GU", "GW", "GY", "HK", "HM", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IN", "IO", "IQ", "IR", "IS", "IT", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KP", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA", "MC", "MD", "MG", "MH", "MK", "ML", "MM", "MN", "MO", "MP", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC", "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW", "PY", "QA", "RE", "RO", "RU", "RW", "SA", "SB", "SC", "SD", "SE", "SG", "SH", "SI", "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "ST", "SV", "SY", "SZ", "TC", "TD", "TF", "TG", "TH", "TJ", "TK", "TM", "TN", "TO", "TL", "TR", "TT", "TV", "TW", "TZ", "UA", "UG", "UM", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VI", "VN", "VU", "WF", "WS", "YE", "YT", "RS", "ZA", "ZM", "ME", "ZW", "A1", "XK", "O1", "AX", "GG", "IM", "JE", "BL", "MF", "BQ", "SS"];
    public $batch_mode = false;
    public $memory_mode = false;
    const SXGEO_FILE = 0;
    const SXGEO_MEMORY = 1;
    const SXGEO_BATCH = 2;
    public function __construct($db_file = "SxGeo.dat", $type = self::SXGEO_FILE)
    {
        $this->fh = fopen($db_file, "rb");
        $header = fread($this->fh, 40);
        if (substr($header, 0, 3) != "SxG") {
            exit("Can't open " . $db_file . "\n");
        }
        $info = unpack("Cver/Ntime/Ctype/Ccharset/Cb_idx_len/nm_idx_len/nrange/Ndb_items/Cid_len/nmax_region/nmax_city/Nregion_size/Ncity_size/nmax_country/Ncountry_size/npack_size", substr($header, 3));
        if ($info["b_idx_len"] * $info["m_idx_len"] * $info["range"] * $info["db_items"] * $info["time"] * $info["id_len"] == 0) {
            exit("Wrong file format " . $db_file . "\n");
        }
        $this->range = $info["range"];
        $this->b_idx_len = $info["b_idx_len"];
        $this->m_idx_len = $info["m_idx_len"];
        $this->db_items = $info["db_items"];
        $this->id_len = $info["id_len"];
        $this->block_len = 3 + $this->id_len;
        $this->max_region = $info["max_region"];
        $this->max_city = $info["max_city"];
        $this->max_country = $info["max_country"];
        $this->country_size = $info["country_size"];
        $this->batch_mode = $type & SXGEO_BATCH;
        $this->memory_mode = $type & SXGEO_MEMORY;
        $this->pack = $info["pack_size"] ? explode("\0", fread($this->fh, $info["pack_size"])) : "";
        $this->b_idx_str = fread($this->fh, $info["b_idx_len"] * 4);
        $this->m_idx_str = fread($this->fh, $info["m_idx_len"] * 4);
        $this->db_begin = ftell($this->fh);
        if ($this->batch_mode) {
            $this->b_idx_arr = array_values(unpack("N*", $this->b_idx_str));
            unset($this->b_idx_str);
            $this->m_idx_arr = str_split($this->m_idx_str, 4);
            unset($this->m_idx_str);
        }
        if ($this->memory_mode) {
            $this->db = fread($this->fh, $this->db_items * $this->block_len);
            $this->regions_db = 0 < $info["region_size"] ? fread($this->fh, $info["region_size"]) : "";
            $this->cities_db = 0 < $info["city_size"] ? fread($this->fh, $info["city_size"]) : "";
        }
        $this->info = $info;
        $this->info["regions_begin"] = $this->db_begin + $this->db_items * $this->block_len;
        $this->info["cities_begin"] = $this->info["regions_begin"] + $info["region_size"];
    }
    protected function search_idx($ipn, $min, $max)
    {
        if ($this->batch_mode) {
            while (8 < $max - $min) {
                $offset = $min + $max >> 1;
                if ($this->m_idx_arr[$offset] < $ipn) {
                    $min = $offset;
                } else {
                    $max = $offset;
                }
            }
            do {
            } while (!($this->m_idx_arr[$min] < $ipn && $min++ < $max));
        } else {
            while (8 < $max - $min) {
                $offset = $min + $max >> 1;
                if (substr($this->m_idx_str, $offset * 4, 4) < $ipn) {
                    $min = $offset;
                } else {
                    $max = $offset;
                }
            }
            do {
            } while (!(substr($this->m_idx_str, $min * 4, 4) < $ipn && $min++ < $max));
        }
        return $min;
    }
    protected function search_db($str, $ipn, $min, $max)
    {
        if (1 < $max - $min) {
            $ipn = substr($ipn, 1);
            while (8 < $max - $min) {
                $offset = $min + $max >> 1;
                if (substr($str, $offset * $this->block_len, 3) < $ipn) {
                    $min = $offset;
                } else {
                    $max = $offset;
                }
            }
            do {
            } while (!(substr($str, $min * $this->block_len, 3) <= $ipn && ++$min < $max));
        } else {
            $min++;
        }
        return hexdec(bin2hex(substr($str, $min * $this->block_len - $this->id_len, $this->id_len)));
    }
    public function get_num($ip)
    {
        $ip1n = (int) $ip;
        if ($ip1n == 0 || $ip1n == 10 || $ip1n == 127 || $this->b_idx_len <= $ip1n || false === ($ipn = ip2long($ip))) {
            return false;
        }
        $ipn = pack("N", $ipn);
        $this->ip1c = chr($ip1n);
        if ($this->batch_mode) {
            $blocks = ["min" => $this->b_idx_arr[$ip1n - 1], "max" => $this->b_idx_arr[$ip1n]];
        } else {
            $blocks = unpack("Nmin/Nmax", substr($this->b_idx_str, ($ip1n - 1) * 4, 8));
        }
        if ($this->range < $blocks["max"] - $blocks["min"]) {
            $part = $this->search_idx($ipn, floor($blocks["min"] / $this->range), floor($blocks["max"] / $this->range) - 1);
            $min = 0 < $part ? $part * $this->range : 0;
            $max = $this->m_idx_len < $part ? $this->db_items : ($part + 1) * $this->range;
            if ($min < $blocks["min"]) {
                $min = $blocks["min"];
            }
            if ($blocks["max"] < $max) {
                $max = $blocks["max"];
            }
        } else {
            $min = $blocks["min"];
            $max = $blocks["max"];
        }
        $len = $max - $min;
        if ($this->memory_mode) {
            return $this->search_db($this->db, $ipn, $min, $max);
        }
        fseek($this->fh, $this->db_begin + $min * $this->block_len);
        return $this->search_db(fread($this->fh, $len * $this->block_len), $ipn, 0, $len);
    }
    protected function readData($seek, $max, $type)
    {
        $raw = "";
        if ($seek && $max) {
            if ($this->memory_mode) {
                $raw = substr($type == 1 ? $this->regions_db : $this->cities_db, $seek, $max);
            } else {
                fseek($this->fh, $this->info[$type == 1 ? "regions_begin" : "cities_begin"] + $seek);
                $raw = fread($this->fh, $max);
            }
        }
        return $this->unpack($this->pack[$type], $raw);
    }
    protected function parseCity($seek, $full = false)
    {
        if (!$this->pack) {
            return false;
        }
        $only_country = false;
        if ($seek < $this->country_size) {
            $country = $this->readData($seek, $this->max_country, 0);
            $city = $this->unpack($this->pack[2]);
            $city["lat"] = $country["lat"];
            $city["lon"] = $country["lon"];
            $only_country = true;
        } else {
            $city = $this->readData($seek, $this->max_city, 2);
            $country = ["id" => $city["country_id"], "iso" => $this->id2iso[$city["country_id"]]];
            unset($city["country_id"]);
        }
        if ($full) {
            $region = $this->readData($city["region_seek"], $this->max_region, 1);
            if (!$only_country) {
                $country = $this->readData($region["country_seek"], $this->max_country, 0);
            }
            unset($city["region_seek"]);
            unset($region["country_seek"]);
            return ["city" => $city, "region" => $region, "country" => $country];
        }
        unset($city["region_seek"]);
        return ["city" => $city, "country" => ["id" => $country["id"], "iso" => $country["iso"]]];
    }
    protected function unpack($pack, $item = "")
    {
        $unpacked = [];
        $empty = empty($item);
        $pack = explode("/", $pack);
        $pos = 0;
        foreach ($pack as $p) {
            list($type, $name) = explode(":", $p);
            $type0 = $type[0];
            if ($empty) {
                $unpacked[$name] = $type0 == "b" || $type0 == "c" ? "" : 0;
            } else {
                switch ($type0) {
                    case "t":
                    case "T":
                        $l = 1;
                        break;
                    case "s":
                    case "n":
                    case "S":
                        $l = 2;
                        break;
                    case "m":
                    case "M":
                        $l = 3;
                        break;
                    case "d":
                        $l = 8;
                        break;
                    case "c":
                        $l = (int) substr($type, 1);
                        break;
                    case "b":
                        $l = strpos($item, "\0", $pos) - $pos;
                        break;
                    case "t":
                        $l = 4;
                        $val = substr($item, $pos, $l);
                        $v = unpack("c", $val);
                        break;
                    case "T":
                        $v = unpack("C", $val);
                        break;
                    case "s":
                        $v = unpack("s", $val);
                        break;
                    case "S":
                        $v = unpack("S", $val);
                        break;
                    case "m":
                        $v = unpack("l", $val . (ord($val[2]) >> 7 ? "�" : "\0"));
                        break;
                    case "M":
                        $v = unpack("L", $val . "\0");
                        break;
                    case "i":
                        $v = unpack("l", $val);
                        break;
                    case "I":
                        $v = unpack("L", $val);
                        break;
                    case "f":
                        $v = unpack("f", $val);
                        break;
                    case "d":
                        $v = unpack("d", $val);
                        break;
                    case "n":
                        $v = current(unpack("s", $val)) / pow(10, $type[1]);
                        break;
                    case "N":
                        $v = current(unpack("l", $val)) / pow(10, $type[1]);
                        break;
                    case "c":
                        $v = rtrim($val, " ");
                        break;
                    case "b":
                        $v = $val;
                        $l++;
                        break;
                    default:
                        $pos += $l;
                        $unpacked[$name] = is_array($v) ? current($v) : $v;
                }
            }
        }
        return $unpacked;
    }
    public function get($ip)
    {
        return $this->max_city ? $this->getCity($ip) : $this->getCountry($ip);
    }
    public function getCountry($ip)
    {
        if ($this->max_city) {
            $tmp = $this->parseCity($this->get_num($ip));
            return $tmp["country"]["iso"];
        }
        return $this->id2iso[$this->get_num($ip)];
    }
    public function getCountryId($ip)
    {
        if ($this->max_city) {
            $tmp = $this->parseCity($this->get_num($ip));
            return $tmp["country"]["id"];
        }
        return $this->get_num($ip);
    }
    public function getCity($ip)
    {
        $seek = $this->get_num($ip);
        return $seek ? $this->parseCity($seek) : false;
    }
    public function getCityFull($ip)
    {
        $seek = $this->get_num($ip);
        return $seek ? $this->parseCity($seek, 1) : false;
    }
    public function about()
    {
        $charset = ["utf-8", "latin1", "cp1251"];
        $types = ["n/a", "SxGeo Country", "SxGeo City RU", "SxGeo City EN", "SxGeo City", "SxGeo City Max RU", "SxGeo City Max EN", "SxGeo City Max"];
        return ["Created" => date("Y.m.d", $this->info["time"]), "Timestamp" => $this->info["time"], "Charset" => $charset[$this->info["charset"]], "Type" => $types[$this->info["type"]], "Byte Index" => $this->b_idx_len, "Main Index" => $this->m_idx_len, "Blocks In Index Item" => $this->range, "IP Blocks" => $this->db_items, "Block Size" => $this->block_len, "City" => ["Max Length" => $this->max_city, "Total Size" => $this->info["city_size"]], "Region" => ["Max Length" => $this->max_region, "Total Size" => $this->info["region_size"]], "Country" => ["Max Length" => $this->max_country, "Total Size" => $this->info["country_size"]]];
    }
}

?>