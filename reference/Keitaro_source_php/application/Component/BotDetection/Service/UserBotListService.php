<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Service;

class UserBotListService extends \Traffic\Service\AbstractService
{
    private $_signatures = ["Advisorbot", "crawler", "oBot", "spider", "ezooms", "FlipboardProxy", "CHTML Proxy", "TweetmemeBot", "bitlybot", "SputnikBot", "Googlebot", "SemrushBot", "YandexBot", "WebIndex", "Slurp", "org_bot", "bot.html", "bot.php", "Twitterbot", "Adsbot", "/bots", "RU_Bot", "OrangeBot", "Synapse", "SEOstats", "urllib", "Owler", "ltx71", "WinHttpRequest", "python-requests", "PageAnalyzer", "OpenLinkProfiler", "BOT for JCE", "BUbiNG", "Nutch", "megaindex", "SeznamBot", "Twitterbot", "bingbot", "facebook", "Google Web Preview", "BingPreview/1.0b", "Exabot-Thumbnails", "coccoc", "Googlebot", "Sleuth", "cmcm.com", "YandexMobileBot", "curl", "Google-Youtube-Links", "MailRuConnect", "vkShare", "SurveyBot", "AppEngine", "NetcraftSurveyAgent"];
    public function isBot($ua, $ip, $options, \Traffic\Logging\TrafficLogEntry $logEntry = NULL)
    {
        if (empty($logEntry)) {
            $logEntry = new \Traffic\Logging\NullTrafficLogEntry();
        }
        if (isset($options["check_bot_empty_ua"]) && $options["check_bot_empty_ua"] && empty($ua)) {
            $logEntry->add("Marked as bot because UserAgent is empty");
            return true;
        }
        if (isset($options["check_bot_ua"]) && $options["check_bot_ua"] && $this->_checkByUa($ua)) {
            $logEntry->add("Bot has been detected by UserAgent");
            return true;
        }
        if (isset($options["check_bot_ip"]) && $options["check_bot_ip"] && $this->_checkByList($ip)) {
            $logEntry->add("Bot has been detected by ip");
            return true;
        }
        return false;
    }
    protected function _checkByUa($ua)
    {
        foreach ($this->_signatures as $signature) {
            if (stristr($ua, $signature)) {
                return true;
            }
        }
        return UserBotSignatureService::instance()->exists($ua);
    }
    protected function _checkByList($ip)
    {
        return \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getRepository()->exists($ip);
    }
}

?>