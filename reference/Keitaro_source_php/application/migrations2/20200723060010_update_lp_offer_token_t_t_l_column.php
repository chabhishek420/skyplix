<?php
use Traffic\Model\Setting;

class Migration_20200723060010_UpdateLpOfferTokenTTLColumn extends Migration 
{
    const DESCRIPTION_RU = 'Обновление поля lp_offer_token_ttl';

    const DESCRIPTION_EN = 'Update lp_offer_token_ttl column';

    public static function up()
    {
        $lp_offer_token_ttl_max = Setting::LP_OFFER_TOKEN_TTL_MAX;
        $prefix = self::getDb()->getPrefix();
        $sql = "UPDATE `{$prefix}settings` SET `value` = '{$lp_offer_token_ttl_max}' WHERE `key` = 'lp_offer_token_ttl' AND `value` > {$lp_offer_token_ttl_max}";
        self::execute($sql);
    }
}