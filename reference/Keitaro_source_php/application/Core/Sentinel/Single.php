<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sentinel;

class Single
{
    public static $supported_algs = ["ES256" => ["openssl", "SHA256"], "HS256" => ["hash_hmac", "SHA256"], "HS384" => ["hash_hmac", "SHA384"], "HS512" => ["hash_hmac", "SHA512"], "RS256" => ["openssl", "SHA256"], "RS384" => ["openssl", "SHA384"], "RS512" => ["openssl", "SHA512"]];
    public static $leeway = 0;
    public static $timestamp = NULL;
    const ASN1_INTEGER = 2;
    const ASN1_SEQUENCE = 16;
    const ASN1_BIT_STRING = 3;
    const SIGNATURE = "23uhrg23urfg2-3urg-2u3hrg-uihwerfun23efz";
    public static function decode($jwt, $key, $allowed_algs = [])
    {
        $timestamp = is_null(self::$timestamp) ? time() : self::$timestamp;
        if (empty($key)) {
            throw new \InvalidArgumentException("Key may not be empty");
        }
        $tks = explode(".", $jwt);
        if (count($tks) != 3) {
            throw new \UnexpectedValueException("Wrong number of segments");
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (NULL === ($header = self::jsonDecode(self::urlsafeB64Decode($headb64)))) {
            throw new \UnexpectedValueException("Invalid header encoding");
        }
        if (NULL === ($payload = self::jsonDecode(self::urlsafeB64Decode($bodyb64)))) {
            throw new \UnexpectedValueException("Invalid claims encoding");
        }
        if (false === ($sig = self::urlsafeB64Decode($cryptob64))) {
            throw new \UnexpectedValueException("Invalid signature encoding");
        }
        if (empty($header->alg)) {
            throw new \UnexpectedValueException("Empty algorithm");
        }
        if (empty(self::$supported_algs[$header->alg])) {
            throw new \UnexpectedValueException("Algorithm not supported");
        }
        if (!in_array($header->alg, $allowed_algs)) {
            throw new \UnexpectedValueException("Algorithm not allowed");
        }
        if ($header->alg === "ES256") {
            $sig = self::signatureToDER($sig);
        }
        if (is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                if (!isset($key[$header->kid])) {
                    throw new \UnexpectedValueException("\"kid\" invalid, unable to lookup correct key");
                }
                $key = $key[$header->kid];
            } else {
                throw new \UnexpectedValueException("\"kid\" empty, unable to lookup correct key");
            }
        }
        if (!self::verify($headb64 . "." . $bodyb64, $sig, $key, $header->alg)) {
            throw new SInvalidException("Signature verification failed");
        }
        if (isset($payload->nbf) && $timestamp + self::$leeway < $payload->nbf) {
            throw new BeforeValidException("Cannot handle token prior to " . date(\DateTime::ISO8601, $payload->nbf));
        }
        if (isset($payload->iat) && $timestamp + self::$leeway < $payload->iat) {
            throw new BeforeValidException("Cannot handle token prior to " . date(\DateTime::ISO8601, $payload->iat));
        }
        if (isset($payload->exp) && $payload->exp <= $timestamp - self::$leeway) {
            throw new ExpiredException("Expired token");
        }
        return $payload;
    }
    public static function encode($payload, $key, $alg = "HS256", $keyId = NULL, $head = NULL)
    {
        $header = ["typ" => "Sentinel", "alg" => $alg];
        if ($keyId !== NULL) {
            $header["kid"] = $keyId;
        }
        if (isset($head) && is_array($head)) {
            $header = array_merge($head, $header);
        }
        $segments = [];
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
        $signing_input = implode(".", $segments);
        $signature = self::sign($signing_input, $key, $alg);
        $segments[] = self::urlsafeB64Encode($signature);
        return implode(".", $segments);
    }
    public static function sign($msg, $key, $alg = "HS256")
    {
        if (empty(self::$supported_algs[$alg])) {
            throw new \DomainException("Algorithm not supported");
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case "hash_hmac":
                return hash_hmac($algorithm, $msg, $key, true);
                break;
            case "openssl":
                $signature = "";
                $success = openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success) {
                    throw new \DomainException("OpenSSL unable to sign data");
                }
                if ($alg === "ES256") {
                    $signature = self::signatureFromDER($signature, 256);
                }
                return $signature;
                break;
        }
    }
    private static function verify($msg, $signature, $key, $alg)
    {
        if (empty(self::$supported_algs[$alg])) {
            throw new \DomainException("Algorithm not supported");
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case "openssl":
                $success = openssl_verify($msg, $signature, $key, $algorithm);
                if ($success === 1) {
                    return true;
                }
                if ($success === 0) {
                    return false;
                }
                throw new \DomainException("OpenSSL error: " . openssl_error_string());
                break;
            case "hash_hmac":
            default:
                $hash = hash_hmac($algorithm, $msg, $key, true);
                if (function_exists("hash_equals")) {
                    return hash_equals($signature, $hash);
                }
                $len = min(self::safeStrlen($signature), self::safeStrlen($hash));
                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= ord($signature[$i]) ^ ord($hash[$i]);
                }
                $status |= self::safeStrlen($signature) ^ self::safeStrlen($hash);
                return $status === 0;
        }
    }
    public static function jsonDecode($input)
    {
        if (version_compare(PHP_VERSION, "5.4.0", ">=") && !(defined("JSON_C_VERSION") && 4 < PHP_INT_SIZE)) {
            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            $max_int_length = strlen((int) PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace("/:\\s*(-?\\d{" . $max_int_length . ",})/", ": \"\$1\"", $input);
            $obj = json_decode($json_without_bigints);
        }
        if ($errno = json_last_error()) {
            self::handleJsonError($errno);
        } else {
            if ($obj === NULL && $input !== "null") {
                throw new \DomainException("Null result with non-null input");
            }
        }
        return $obj;
    }
    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if ($errno = json_last_error()) {
            self::handleJsonError($errno);
        } else {
            if ($json === "null" && $input !== NULL) {
                throw new \DomainException("Null result with non-null input");
            }
        }
        return $json;
    }
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat("=", $padlen);
        }
        return base64_decode(strtr($input, "-_", "+/"));
    }
    public static function urlsafeB64Encode($input)
    {
        return str_replace("=", "", strtr(base64_encode($input), "+/", "-_"));
    }
    private static function handleJsonError($errno)
    {
        $messages = [JSON_ERROR_DEPTH => "Maximum stack depth exceeded", JSON_ERROR_STATE_MISMATCH => "Invalid or malformed JSON", JSON_ERROR_CTRL_CHAR => "Unexpected control character found", JSON_ERROR_SYNTAX => "Syntax error, malformed JSON", JSON_ERROR_UTF8 => "Malformed UTF-8 characters"];
        throw new \DomainException(isset($messages[$errno]) ? $messages[$errno] : "Unknown JSON error: " . $errno);
    }
    private static function safeStrlen($str)
    {
        if (function_exists("mb_strlen")) {
            return mb_strlen($str, "8bit");
        }
        return strlen($str);
    }
    private static function signatureToDER($sig)
    {
        list($r, $s) = str_split($sig, (int) (strlen($sig) / 2));
        $r = ltrim($r, "\0");
        $s = ltrim($s, "\0");
        if (127 < ord($r[0])) {
            $r = "\0" . $r;
        }
        if (127 < ord($s[0])) {
            $s = "\0" . $s;
        }
        return self::encodeDER(ASN1_SEQUENCE, self::encodeDER(ASN1_INTEGER, $r) . self::encodeDER(ASN1_INTEGER, $s));
    }
    private static function encodeDER($type, $value)
    {
        $tag_header = 0;
        if ($type === ASN1_SEQUENCE) {
            $tag_header |= 32;
        }
        $der = chr($tag_header | $type);
        $der .= chr(strlen($value));
        return $der . $value;
    }
    private static function signatureFromDER($der, $keySize)
    {
        list($offset, $_) = self::readDER($der);
        list($offset, $r) = self::readDER($der, $offset);
        list($offset, $s) = self::readDER($der, $offset);
        $r = ltrim($r, "\0");
        $s = ltrim($s, "\0");
        $r = str_pad($r, $keySize / 8, "\0", STR_PAD_LEFT);
        $s = str_pad($s, $keySize / 8, "\0", STR_PAD_LEFT);
        return $r . $s;
    }
    private static function readDER($der, $offset = 0)
    {
        $pos = $offset;
        $size = strlen($der);
        $constructed = ord($der[$pos]) >> 5 & 1;
        $type = ord($der[$pos++]) & 31;
        $len = ord($der[$pos++]);
        if ($len & 128) {
            $n = $len & 31;
            $len = 0;
            while ($n-- && $pos < $size) {
                $len = $len << 8 | ord($der[$pos++]);
            }
        }
        if ($type == ASN1_BIT_STRING) {
            $pos++;
            $data = substr($der, $pos, $len - 1);
            $pos += $len - 1;
        } else {
            if (!$constructed) {
                $data = substr($der, $pos, $len);
                $pos += $len;
            } else {
                $data = NULL;
            }
        }
        return [$pos, $data];
    }
}

?>