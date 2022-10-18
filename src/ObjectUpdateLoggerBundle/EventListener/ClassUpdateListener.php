<?php

namespace Lemonmind\ObjectUpdateLoggerBundle\EventListener;

use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Log\Simple;
use Pimcore\Model\User;

class ClassUpdateListener
{
    public function postUpdateClassDefinition(ClassDefinitionEvent $event): void
    {
        if ($event instanceof ClassDefinitionEvent) {
            $classDefinition = $event->getClassDefinition();
            $user = User::getById($classDefinition->getUserModification());
            Simple::log('updateLogger', '==========================================');
            Simple::log('updateLogger', 'Class ' . $classDefinition->getName() . ' has been updated');
            Simple::log('updateLogger', 'modification date: ' . date("Y-m-d H:i:s"));
            Simple::log('updateLogger', 'user id: ' . $user->getId());
            Simple::log('updateLogger', 'user email: ' . $user->getEmail());
        }
    }
}
