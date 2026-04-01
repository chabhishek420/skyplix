<?php
class Migration_20200420153756_AddDomainSSLStatusColumn extends Migration 
{
    const DESCRIPTION_RU = 'Добавление колонки ssl_status в таблицу доменов';

    const DESCRIPTION_EN = 'Adding ssl_status column to domains table';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER TABLE {$prefix}domains
                ADD ssl_status varchar(255)";
        self::silentExecute($sql);
    }
}