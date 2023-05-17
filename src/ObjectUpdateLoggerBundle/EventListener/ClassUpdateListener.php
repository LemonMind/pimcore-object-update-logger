<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle\EventListener;

use Lemonmind\ObjectUpdateLoggerBundle\Service\LogService;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Model\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

readonly class ClassUpdateListener
{
    public function __construct(
        private LogService $logService,
    ) {
    }

    public function postUpdateClassDefinition(ClassDefinitionEvent $event): void
    {
        if (\Pimcore::inAdmin()) {
            $container = \Pimcore::getContainer();

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
        $user = null;

        if (is_int($classDefinition->getUserModification())) {
            $user = User::getById($classDefinition->getUserModification());
        }
        $this->logService->log('updateLogger', '==========================================');
        $this->logService->log('updateLogger', 'Class ' . $classDefinition->getName() . ' has been updated');
        $this->logService->log('updateLogger', 'modification date: ' . date('Y-m-d H:i:s'));

        if ($user) {
            $this->logService->log('updateLogger', 'user id: ' . $user->getId());
            $this->logService->log('updateLogger', 'user email: ' . $user->getEmail());
        }
    }
}
