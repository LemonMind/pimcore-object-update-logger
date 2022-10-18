<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle\EventListener;

use Pimcore;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Log\Simple;
use Pimcore\Model\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ClassUpdateListener
{
    public function postUpdateClassDefinition(ClassDefinitionEvent $event): void
    {
        if (Pimcore::inAdmin()) {
            $container = Pimcore::getContainer();

            if ($container instanceof ContainerInterface) {
                $config = $container->getParameter('lemonmind_object_update_logger');
                $classesToLog = $config['classesToLog'];

                if ($config['disableClassLog']) {
                    return;
                }

                if (null === $classesToLog) {
                    $this->log($event);
                } elseif (in_array($event->getClassDefinition()->getName(), $classesToLog, true)) {
                    $this->log($event);
                }
            }
        }
    }

    public function log(ClassDefinitionEvent $event): void
    {
        $classDefinition = $event->getClassDefinition();
        $user = User::getById($classDefinition->getUserModification());
        Simple::log('updateLogger', '==========================================');
        Simple::log('updateLogger', 'Class ' . $classDefinition->getName() . ' has been updated');
        Simple::log('updateLogger', 'modification date: ' . date('Y-m-d H:i:s'));
        Simple::log('updateLogger', 'user id: ' . $user->getId());
        Simple::log('updateLogger', 'user email: ' . $user->getEmail());
    }
}
