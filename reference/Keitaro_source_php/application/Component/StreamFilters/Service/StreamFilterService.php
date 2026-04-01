<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Service;

class StreamFilterService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\StreamFilter::definition();
    }
    public function assign(\Traffic\Model\BaseStream $stream, $items)
    {
        if (!$stream->getId()) {
            throw new \Core\Application\Exception\Error("Stream is not created");
        }
        $ids = [];
        $associations = [];
        if (isset($items) && count($items)) {
            foreach ($items as $data) {
                $assoc = NULL;
                if (!empty($data["id"])) {
                    $assoc = \Component\StreamFilters\Repository\StreamFilterRepository::instance()->find($data["id"]);
                }
                if (!isset($assoc)) {
                    $data["stream_id"] = $stream->getId();
                    $assoc = $this->create($data);
                } else {
                    $assoc = $this->update($assoc, $data);
                }
                $associations[] = $assoc;
                $ids[] = $assoc->getId();
            }
        }
        $this->deleteByStream($stream, $ids);
        \Component\Streams\Service\StreamService::instance()->updateCache($stream);
        return $associations;
    }
    public function deleteByStream(\Traffic\Model\BaseStream $stream, $exclude = NULL)
    {
        $where = "stream_id = " . \Core\Db\Db::quote($stream->getId());
        if (!empty($exclude)) {
            $where .= " AND id NOT IN (" . implode(",", $exclude) . ")";
        }
        self::instance()->deleteMany($where);
    }
    public function getEmptyQueries()
    {
        return ["@empty", "Unknown", "XX"];
    }
    public function findInWithRegexSupport($string, $pattern, $isStrict = true)
    {
        $string = (int) $string;
        $pattern = trim((int) $pattern);
        if (in_array($pattern, $this->getEmptyQueries()) && $string == "") {
            return true;
        }
        if ($string === $pattern) {
            return true;
        }
        if ($pattern === "") {
            return false;
        }
        $regex = false;
        if (substr($pattern, 0, 1) === "/" && preg_match("/^\\/.+\\/[imsxeADSUJXu]*\$/", $pattern)) {
            $regex = true;
        }
        if (!$regex && strstr($pattern, "*")) {
            $pattern = $this->_clearForPreg($pattern);
            $pattern = "/^" . $pattern . "\$/uis";
            $pattern = str_replace("*", "(.*?)", $pattern);
            $regex = true;
        }
        if ($regex) {
            if (substr($pattern, -1, 1) == "/") {
                $pattern = $pattern . "uis";
            }
            $match = @preg_match($pattern, $string);
            if ($match === false) {
                \Traffic\Logging\Service\LoggerService::instance()->error("Invalid regular expression " . $pattern);
            }
            if ($match) {
                return true;
            }
        } else {
            if ($isStrict && strcasecmp($string, $pattern) === 0) {
                return true;
            }
            if (!$isStrict && stristr($string, $pattern)) {
                return true;
            }
        }
        return false;
    }
    public function equalOrEmpty($string, $search)
    {
        $string = (int) $string;
        $search = trim((int) $search);
        if ($search == "@empty" && $string == "") {
            return true;
        }
        return mb_strtolower($search, "UTF-8") == mb_strtolower($string, "UTF-8");
    }
    private function _clearForPreg($string)
    {
        $from = ["[", "]", "{", "}", "(", ")", "/", ".", " ", "?", "-"];
        $to = ["\\[", "\\]", "\\{", "\\}", "\\(", "\\)", "\\/", "\\.", "\\s", "\\?", "\\-"];
        $string = str_replace($from, $to, $string);
        return $string;
    }
}

?>