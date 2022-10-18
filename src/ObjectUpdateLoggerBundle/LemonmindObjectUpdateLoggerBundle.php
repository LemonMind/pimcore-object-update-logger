<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class LemonmindObjectUpdateLoggerBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    protected function getComposerPackageName(): string
    {
        return 'lemonmind/pimcore-object-update-logger';
    }

    public function getJsPaths()
    {
        return [
            '/bundles/lemonmindobjectupdatelogger/js/pimcore/startup.js',
        ];
    }
}
