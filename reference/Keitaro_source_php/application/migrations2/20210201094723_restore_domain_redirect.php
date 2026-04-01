<?php
class Migration_20210201094723_RestoreDomainRedirect extends Migration
{
    const DESCRIPTION_RU = 'Восстановление редиректов доменов';

    const DESCRIPTION_EN = 'Restore domains redirects';

    // Re-migration, for those who updated from 9.11.5 to 9.12.1.2
    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "UPDATE {$prefix}domains SET ssl_redirect = 1 WHERE redirect = 'https'";
        self::silentExecute($sql);
    }
}
