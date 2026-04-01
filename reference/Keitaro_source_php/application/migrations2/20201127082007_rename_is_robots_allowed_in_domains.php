<?php

class Migration_20201127082007_RenameIsRobotsAllowedInDomains extends Migration
{
    const DESCRIPTION_RU = 'Переименовывание is_robots_allowed в domains';

    const DESCRIPTION_EN = 'Rename is_robots_allowed in domains';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}domains ADD COLUMN allow_indexing tinyint(1) unsigned DEFAULT 1";
        self::silentExecute($sql);

        $sql = "UPDATE {$prefix}domains SET allow_indexing = 0 WHERE is_robots_allowed = 0";
        self::silentExecute($sql);
    }

    public function down()
    {
        $prefix = self::getPrefix();
        $sql = "UPDATE {$prefix}domains SET is_robots_allowed = 0 WHERE allow_indexing = 0";
        self::silentExecute($sql);
    }
}
