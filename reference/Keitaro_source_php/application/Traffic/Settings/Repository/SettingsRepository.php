<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Settings\Repository;

class SettingsRepository extends \Traffic\Repository\AbstractBaseRepository
{
    const NEW_STYLE = "new";
    const OLD_STYLE = "old";
    public function findByKey($key)
    {
        $where = "`key` = " . \Core\Db\Db::quote($key);
        return \Core\Db\DataRepository::instance()->findFirst(\Traffic\Model\Setting::definition(), $where);
    }
    public function allAsHash($only = NULL)
    {
        $settings = [];
        if ($only) {
            if (!is_array($only)) {
                $only = [$only];
            }
            $where = "`key` IN (" . implode(",", \Core\Db\Db::quote($only)) . ")";
        } else {
            $where = NULL;
        }
        $rows = \Core\Db\DataRepository::instance()->rawRows(\Traffic\Model\Setting::definition(), NULL, $where);
        foreach ($rows as $row) {
            $settings[$row["key"]] = $row["value"];
        }
        return $settings;
    }
    public function getLinkFormats(\Psr\Http\Message\UriInterface $uri)
    {
        return [["value" => OLD_STYLE, "name" => $this->_generateLink($uri, "alias", OLD_STYLE)], ["value" => NEW_STYLE, "name" => $this->_generateLink($uri, "alias", NEW_STYLE)]];
    }
    private function _generateLink(\Psr\Http\Message\UriInterface $uri, $page, $style)
    {
        $str = \Traffic\Service\UrlService::instance()->getBaseUrl($uri) . "/";
        if ($style == OLD_STYLE) {
            $str .= "?";
        }
        $str .= $page;
        return $str;
    }
}

?>