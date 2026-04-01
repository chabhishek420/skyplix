<?php
class Migration_20210126194546_RestoreStreamShare extends Migration
{
    const DESCRIPTION_RU = 'Восстановление долей потоков';

    const DESCRIPTION_EN = 'Restore stream share';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "SELECT sum(`weight`) as sum FROM `{$prefix}streams`";
        $row = self::getDb()->getRow($sql);
        if (empty($row) || !is_array($row)) {
            return;
        }
        if ($row['sum'] == 0) {
            $sql = "UPDATE {$prefix}streams SET weight = position WHERE weight = 0";
            self::silentExecute($sql);
        }
    }
}
