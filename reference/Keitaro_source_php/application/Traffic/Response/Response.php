<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Response;

class Response extends \GuzzleHttp\Psr7\Response implements \Psr\Http\Message\ResponseInterface
{
    public static function build($options = [])
    {
        return ResponseFactory::build($options);
    }
    public static function buildJson($options = [])
    {
        $response = self::build($options);
        $response = $response->withHeader(ContentType::HEADER, ContentType::JSON);
        return $response;
    }
    public static function buildHtml($options = [])
    {
        $response = self::build($options);
        $response = $response->withHeader(ContentType::HEADER, ContentType::HTML);
        return $response;
    }
    public function withHeader($header, $value)
    {
        return self::withHeader($header, $value);
    }
    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {
        return self::withBody($body);
    }
    public function withStatus($code, $reasonPhrase = "")
    {
        return self::withStatus($code, $reasonPhrase);
    }
    public function serialize()
    {
        $data = ["status" => $this->getStatusCode(), "headers" => $this->getHeaders(), "body" => (int) $this->getBody()];
        return json_encode($data);
    }
    public function disableCache()
    {
        return $this->withHeader("Last-Modified", gmdate("D, d M Y H:i:s") . " GMT")->withHeader("Cache-Control", "no-cache, no-store, must-revalidate,post-check=0,pre-check=0")->withHeader("Pragma", "no-cache")->withHeader("Expires", "0");
    }
}

?>