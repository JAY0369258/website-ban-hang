<?php
function logError($message)
{
    $log_file = __DIR__ . '/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}
