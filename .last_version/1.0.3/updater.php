<?php

use Bitrix\Main\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$moduleID = 'wappi.whatsapptelegram';

if ($updater->CanUpdateDatabase()) {
    if ($updater->PreviousVersion < '1.0.2') {
        $connection = Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        
        $tableName = 'wappipro_template';
        $checkTable = $connection->isTableExists($tableName);

        if ($checkTable) {
            $connection->queryExecute("ALTER TABLE $tableName CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        }
    }
}

?>
