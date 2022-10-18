<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class LemonmindObjectUpdateLoggerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $lemonmindObjectUpdateLoggerConfig['classesToLog'] = null;
        $lemonmindObjectUpdateLoggerConfig['objectsToLog'] = null;

        if (isset($config['classes_to_log'])) {
            $classes = explode(',', $config['classes_to_log']);
            $arr = [];

            foreach ($classes as $class) {
                $arr[] = str_replace(' ', '', $class);
            }

            $lemonmindObjectUpdateLoggerConfig['classesToLog'] = $arr;
        }

        if (isset($config['objects_to_log'])) {
            $objects = explode(',', $config['objects_to_log']);
            $arr = [];

            foreach ($objects as $object) {
                $arr[] = str_replace(' ', '', $object);
            }

            $lemonmindObjectUpdateLoggerConfig['objectsToLog'] = $arr;
        }

        $container->setParameter('lemonmind_object_update_logger', $lemonmindObjectUpdateLoggerConfig);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
