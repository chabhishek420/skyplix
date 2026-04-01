<?php

class Migration_20180809124704_AddRboosterConfigSection extends Migration
{
    const DESCRIPTION_RU = 'Добавление конфигурации rbooster';

    const DESCRIPTION_EN = 'Add rbooster configuration';

    public static function up()
    {
        if (!strstr(file_get_contents(ROOT . '/application/config/config.ini.php'), 'clickhouse')) {
            self::addToConfig([
                '[clickhouse]',
                'ch_host = #clickhouse_server#',
                'ch_port = #clickhouse_port#',
                'ch_user = #clickhouse_user#',
                'ch_password = "#clickhouse_password#"',
                'ch_db = "#clickhouse_name#"',
                'ch_timeout = 4',
                'ch_connect_timeout = 5',
                'ch_log_queries = false',
            ]);
        }
    }
}
