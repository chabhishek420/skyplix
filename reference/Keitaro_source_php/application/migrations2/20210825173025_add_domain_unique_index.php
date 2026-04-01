<?php

class Migration_20210825173025_AddDomainUniqueIndex extends Migration
{
    public const DESCRIPTION_RU = 'Добавление уникального индекса для имени домена';

    public const DESCRIPTION_EN = 'Adding a unique index for a domain name';

    public static function up()
    {
        $prefix = self::getDb()->getPrefix();

        // Rename race condition duplicates
        $sql = "SELECT DISTINCT(domains1.id), domains1.* FROM {$prefix}domains domains1 INNER JOIN {$prefix}domains domains2
                WHERE domains1.id > domains2.id AND domains1.name = domains2.name
                AND domains1.state = 'active' AND domains2.state = 'active'";
        $rows = self::getDb()->getAll($sql);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $name = $row['name'] . '-duplicate' . $row['id'] . '-' . $row['state'];
                $sqlUpdate = "UPDATE {$prefix}domains SET name = '{$name}', state = 'deleted' WHERE id = {$row['id']}";
                self::silentExecute($sqlUpdate);
            }
        }

        // Rename deleted domains that have the same active domain
        $sql = "SELECT DISTINCT(domains1.id), domains1.* FROM {$prefix}domains domains1 INNER JOIN {$prefix}domains domains2 
                WHERE domains1.id != domains2.id AND domains1.name = domains2.name 
                AND domains1.state = 'deleted' AND domains2.state = 'active'";
        $rows = self::getDb()->getAll($sql);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $name = $row['name'] . '-duplicate' . $row['id'] . '-' . $row['state'];
                $sqlUpdate = "UPDATE {$prefix}domains SET name = '{$name}' WHERE id = {$row['id']}";
                self::silentExecute($sqlUpdate);
            }
        }

        // Add unique index
        $sql = "ALTER TABLE {$prefix}domains ADD UNIQUE INDEX name (name)";
        self::silentExecute($sql);
    }
}
