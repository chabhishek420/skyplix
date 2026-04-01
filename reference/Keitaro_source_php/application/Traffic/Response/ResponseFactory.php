<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Response;

class ResponseFactory
{
    private static $_allowedOptions = ["headers", "body", "status", "disable_cache"];
    const DEFAULT_STATUS = 200;
    public static function build($options = [])
    {
        foreach ($options as $key => $value) {
            if (!in_array($key, self::$_allowedOptions)) {
                throw new ResponseError("Incorrect option " . $key);
            }
        }
        if (!empty($options["headers"])) {
            $headers = $options["headers"];
        } else {
            $headers = [];
        }
        $status = isset($options["status"]) ? $options["status"] : DEFAULT_STATUS;
        $response = new Response($status, $headers);
        if (isset($options["disable_cache"])) {
            $response = $response->disableCache();
        }
        if (!empty($options["body"])) {
            $response = $response->withBody(ResponseFactory::safeBody($options["body"]));
        }
        return $response;
    }
    public static function safeBody($rawBody)
    {
        if ($rawBody instanceof \Psr\Http\Message\StreamInterface) {
            return $rawBody;
        }
        if (is_array($rawBody) || is_object($rawBody)) {
            $body = json_encode($rawBody);
        } else {
            $body = $rawBody;
        }
        return GuzzleHttp\Psr7\stream_for($body);
    }
    public static function parseHeaderString($headerString)
    {
        $pos = strpos($headerString, ":");
        $value = substr($headerString, $pos + 1);
        $name = substr($headerString, 0, $pos);
        return ["name" => trim($name), "value" => trim($value)];
    }
}

?>