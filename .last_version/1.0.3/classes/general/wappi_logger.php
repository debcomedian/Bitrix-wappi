<?php
// Файл: /bitrix/modules/wappi.whatsapptelegram/classes/general/WappiLogger.php

class WappiLogger {
    /*
     * Записывает сообщение в лог-файл внутри директории модуля.
     *
     * @param string $message    Сообщение для записи.
     * @param string $file       Имя файла лога. По умолчанию "wappi_log.log".
     * @param string $directory  Путь к директории логов относительно директории модуля. По умолчанию "wappi_logs/".
     * @return bool              Возвращает true при успешной записи, false в случае ошибки.
     */
    public static function writeLog($message, $file = "wappi_log.log", $directory = "wappi_logs/") {
        $moduleDir = dirname(__FILE__, 3);
        
        $logDirPath = $moduleDir . '/' . $directory;
        
        $filePath = $logDirPath . $file;
        
        $date = date("Y-m-d H:i:s");
        $formattedMessage = "[" . $date . "] " . $message . PHP_EOL;
        
        if (!file_exists($logDirPath)) {
            if (!mkdir($logDirPath, 0755, true)) {
                error_log("Не удалось создать директорию для логов: " . $logDirPath);
                return false;
            }
        }
        
        if (file_exists($filePath) && !is_writable($filePath)) {
            if (!chmod($filePath, 0644)) {
                error_log("Файл лога недоступен для записи и не удалось изменить права: " . $filePath);
                return false;
            }
        }
        
        $result = file_put_contents($filePath, $formattedMessage, FILE_APPEND | LOCK_EX);
        
        if ($result === false) {
            error_log("Не удалось записать в файл лога: " . $filePath);
            return false;
        }
        
        return true;
    }

    /*
     * Дополнительный метод для логирования ошибок.
     *
     * @param string $errorMessage Сообщение об ошибке.
     * @return bool                Возвращает true при успешной записи, false в случае ошибки.
     */
    public static function writeError($errorMessage) {
        return self::writeLog("ERROR: " . $errorMessage, "wappi_error.log");
    }

    /**
     * Дополнительный метод для логирования информационных сообщений.
     *
     * @param string $infoMessage Информационное сообщение.
     * @return bool                Возвращает true при успешной записи, false в случае ошибки.
     */
    public static function writeInfo($infoMessage) {
        return self::writeLog("INFO: " . $infoMessage, "wappi_info.log");
    }

    /**
     * Обход массива и логирование ключей и значений.
     *
     * @param array $array Массив для обработки.
     * @param string $prefix Префикс для логирования (для вложенных элементов).
     * @return void
     */
    public static function logArrayKeysAndValues($array, $prefix = '') {
        foreach ($array as $key => $value) {
            // Если значение — массив, обходим его рекурсивно
            if (is_array($value)) {
                self:logArrayKeysAndValues($value, $prefix . $key . " -> ");
            } else {
                // Форматируем ключ и значение
                $formattedEntry = "{$prefix}{$key}: " . (is_null($value) ? "NULL" : $value);
                // Логируем каждую пару ключ-значение
                WappiLogger::writeLog($formattedEntry);
            }
        }
    }

}