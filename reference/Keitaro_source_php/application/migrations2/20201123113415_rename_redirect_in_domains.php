<?php

class Migration_20201123113415_RenameRedirectInDomains extends Migration
{
    const DESCRIPTION_RU = 'Переименовывание redirect в domains';

    const DESCRIPTION_EN = 'Rename redirect in domains';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}domains ADD COLUMN ssl_redirect tinyint(1) unsigned DEFAULT 0";
        self::silentExecute($sql);

        $sql = "UPDATE {$prefix}domains SET ssl_redirect = 1 WHERE redirect = 'https'";
        self::silentExecute($sql);
    }

    public function down()
    {
        $prefix = self::getPrefix();
        $sql = "UPDATE {$prefix}domains SET redirect = 'https' WHERE ssl_redirect = 1";
        self::silentExecute($sql);
    }
}