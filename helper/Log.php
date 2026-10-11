<?php

class Log
{
    private static function write($level, $message)
    {
        $logDir = __DIR__ . '/../log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $file = $logDir . '/' . date('Y-m-d') . '.log';
        $line = sprintf("[%s] [%s] %s\n", date('H:i:s'), strtoupper($level), $message);
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info($message)
    {
        self::write('info', $message);
    }

    public static function warning($message)
    {
        self::write('warning', $message);
    }

    public static function error($message)
    {
        self::write('error', $message);
    }
}
