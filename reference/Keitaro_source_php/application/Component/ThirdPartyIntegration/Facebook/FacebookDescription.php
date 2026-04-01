<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Facebook;

class FacebookDescription
{
    public static function getDescriptionRu()
    {
        $description = "Настройка интеграции для получения расходов рекламных компаниий в Facebook через API<br>\n<h3>Инструкция по настройке</h3>\n<p>Для настройки вам необходимо добавить данные для подключения. Нажмите на кнопку \"Добавить новую\". В открывшемся окне вам необходимо\nввести соответствующие данные для подключения к Facebook.</p>\n<ul>\n    <li>Имя интеграции - произвольное имя необходимое для удобной идентификации в списке интеграции</li>\n    <li>ID кабинета Facebook - идентификатор вашего платежного кабинета (ID рекламного аккаунта)</li>\n    <li>Токен - токен для подключения к рекламному аккаунту через API</li>\n    <li>Использовать прокси - получать данные через прокси (укажите настройки прокси)</li>\n</ul>\n<p>После добавления интеграции вам необходимо к ней подключить кампанию. Для этого нажмити на икноку <i class=\"ion ion-link\"></i> и выбирите кампании для которых будет импортироваться расход.</p>\n<p>Важно!!! Для работы интеграции в вашей кампании должен быть параметр {{adset.id}}. Данные синхронизируются по adset.id</p>\n<p>Дополнительную информацию читайте в блоге <a href=\"https://blog.keitaro.io/ru/costs-facebook/\" target=\"_blank\">автоматическая загрузка расходов с Facebook в Keitaro</a></p>";
        return ["description" => $description];
    }
    public static function getDescriptionEn()
    {
        $description = "Set up integration to receive expenses of advertising campaigns in Facebook via API<br>\n<h3>Configuration instructions</h3>\n<p>To configure, you need to add connection data. Click on the \"Add new\" button. In the window that opens, you need to enter the appropriate data to connect to Facebook.</p>\n<ul>\n    <li>Integration name - arbitrary name needed for easy identification in the integration list</li>\n    <li>ID cabinet Facebook - your payment account ID (ad account ID)</li>\n    <li>Token - token to connect to an advertising account through the API</li>\n    <li>Use proxy - to obtain data via proxy (specify the proxy settings)</li>\n</ul>\n<p>After adding the integration, you need to connect the campaign to it. To do this, click the icon <i class=\"ion ion-link\"></i> and select the campaigns for which the cost will be imported.</p>\n<p>Important!!! For integration to work in your campaign, there must be a parameter {{adset.id}}. Data is synchronized by adset.id</p>\n<p>Read the blog for more information <a href=\"https://blog.keitaro.io/en/costs-facebook/\" target=\"_blank\">auto-updating costs from Facebook in Keitaro</a></p>";
        return ["description" => $description];
    }
}

?>