<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle\Service;

use Pimcore\File;

class LogService
{
    public function log($name, $message): void
    {
        $log = PIMCORE_LOG_DIRECTORY . "/$name.log";
        clearstatcache(true, $log);

        if (!is_file($log)) {
            if (is_writable(dirname($log))) {
                File::putPhpFile($log, "AUTOCREATE\n");
            }
        }

        if (is_writable($log)) {
            if (filesize($log) > 200000000) {
                File::putPhpFile($log, '');
            }

            $date = new \DateTime('now');

            $f = fopen($log, 'a+');
            fwrite($f, $date->format('Y-m-d\TH:i:sO') . ' : ' . $message . "\n");
            fclose($f);
        }
    }
}
