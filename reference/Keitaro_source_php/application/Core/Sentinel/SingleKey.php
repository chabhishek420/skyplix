<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sentinel;

class SingleKey
{
    public static function parseKeySet($jwks)
    {
        $keys = [];
        if (!isset($jwks["keys"])) {
            throw new \UnexpectedValueException("\"keys\" member must exist in the JWK Set");
        }
        if (empty($jwks["keys"])) {
            throw new \InvalidArgumentException("JWK Set did not contain any keys");
        }
        foreach ($jwks["keys"] as $k => $v) {
            $kid = isset($v["kid"]) ? $v["kid"] : $k;
            if ($key = self::parseKey($v)) {
                $keys[$kid] = $key;
            }
        }
        if (0 === count($keys)) {
            throw new \UnexpectedValueException("No supported algorithms found in JWK Set");
        }
        return $keys;
    }
    private static function parseKey($jwk)
    {
        if (empty($jwk)) {
            throw new \InvalidArgumentException("JWK must not be empty");
        }
        if (!isset($jwk["kty"])) {
            throw new \UnexpectedValueException("JWK must contain a \"kty\" parameter");
        }
        switch ($jwk["kty"]) {
            case "RSA":
                if (array_key_exists("d", $jwk)) {
                    throw new \UnexpectedValueException("RSA private keys are not supported");
                }
                if (!isset($jwk["n"]) || !isset($jwk["e"])) {
                    throw new \UnexpectedValueException("RSA keys must contain values for both \"n\" and \"e\"");
                }
                $pem = self::createPemFromModulusAndExponent($jwk["n"], $jwk["e"]);
                $publicKey = openssl_pkey_get_public($pem);
                if (false === $publicKey) {
                    throw new \DomainException("OpenSSL error: " . openssl_error_string());
                }
                return $publicKey;
                break;
        }
    }
    private static function createPemFromModulusAndExponent($n, $e)
    {
        $modulus = Single::urlsafeB64Decode($n);
        $publicExponent = Single::urlsafeB64Decode($e);
        $components = ["modulus" => pack("Ca*a*", 2, self::encodeLength(strlen($modulus)), $modulus), "publicExponent" => pack("Ca*a*", 2, self::encodeLength(strlen($publicExponent)), $publicExponent)];
        $rsaPublicKey = pack("Ca*a*a*", 48, self::encodeLength(strlen($components["modulus"]) + strlen($components["publicExponent"])), $components["modulus"], $components["publicExponent"]);
        $rsaOID = pack("H*", "300d06092a864886f70d0101010500");
        $rsaPublicKey = chr(0) . $rsaPublicKey;
        $rsaPublicKey = chr(3) . self::encodeLength(strlen($rsaPublicKey)) . $rsaPublicKey;
        $rsaPublicKey = pack("Ca*a*", 48, self::encodeLength(strlen($rsaOID . $rsaPublicKey)), $rsaOID . $rsaPublicKey);
        $rsaPublicKey = "-----BEGIN PUBLIC KEY-----\r\n" . chunk_split(base64_encode($rsaPublicKey), 64) . "-----END PUBLIC KEY-----";
        return $rsaPublicKey;
    }
    private static function encodeLength($length)
    {
        if ($length <= 127) {
            return chr($length);
        }
        $temp = ltrim(pack("N", $length), chr(0));
        return pack("Ca*", 128 | strlen($temp), $temp);
    }
}

?>