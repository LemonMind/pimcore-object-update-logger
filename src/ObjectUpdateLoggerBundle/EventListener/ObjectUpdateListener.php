<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle\EventListener;

use Pimcore;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Log\Simple;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\Version;
use Pimcore\Tool;
use Pimcore\Twig\Extension\PimcoreObjectExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ObjectUpdateListener
{
    public function postUpdate(DataObjectEvent $event): void
    {
        if (Pimcore::inAdmin()) {
            $container = Pimcore::getContainer();

            if ($container instanceof ContainerInterface) {
                $config = $container->getParameter('lemonmind_object_update_logger');
                $objectsToLog = $config['objectsToLog'];

                if ($config['disableObjectLog']) {
                    return;
                }

                $object = DataObject::getByPath($event->getObject()->getCurrentFullPath());

                if ($object instanceof DataObject) {
                    if (null === $objectsToLog) {
                        $this->log($object);
                    } elseif (in_array($object->getClass()->getName(), $objectsToLog, true)) {
                        $this->log($object);
                    }
                }
            }
        }
    }

    private function log(DataObject $object): void
    {
        $versions = $object->getVersions();

        if (count($versions) > 1) {
            $currentVersion = $versions[count($versions) - 1];
            $previousVersion = $versions[count($versions) - 2];

            DataObject::setDoNotRestoreKeyAndPath(true);
            $currentObject = $currentVersion->getData();
            $previousObject = $previousVersion->getData();

            $this->defaultInformation($currentVersion, $currentObject);

            $validLanguages = Tool::getValidLanguages();

            if (method_exists($currentObject, 'getLocalizedFields')) {
                /** @var DataObject\Localizedfield $localizedFieldsCurrent */
                $localizedFieldsCurrent = $currentObject->getLocalizedFields();
                $localizedFieldsCurrent->setLoadedAllLazyData();
            }

            if (method_exists($previousObject, 'getLocalizedFields')) {
                /** @var DataObject\Localizedfield $localizedFieldsPrevious */
                $localizedFieldsPrevious = $previousObject->getLocalizedFields();
                $localizedFieldsPrevious->setLoadedAllLazyData();
            }

            DataObject::setDoNotRestoreKeyAndPath(false);

            if ($currentObject && $previousObject) {
                if ($currentObject->isAllowed('versions') && $previousObject->isAllowed('versions')) {
                    $fields = $currentObject->getClass()->getFieldDefinitions();

                    if ($currentObject->getRealFullPath() !== $previousObject->getRealFullPath()) {
                        Simple::log('updateLogger', 'path: ' . $previousObject->getRealFullPath() . ' -> ' . $currentObject->getRealFullPath());
                    }

                    if ($currentObject->getPublished() !== $previousObject->getPublished()) {
                        Simple::log('updateLogger', 'published: ' . $previousObject->getPublished() . ' -> ' . $currentObject->getPublished());
                    }

                    foreach ($fields as $fieldName => $definition) {
                        if ($definition instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                            $this->localizedFields($definition, $currentObject, $previousObject, $fieldName, $validLanguages);
                        } elseif ($definition instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                            $this->objectBricks($definition, $currentObject, $previousObject, $fieldName, $validLanguages);
                        } elseif ($definition instanceof DataObject\ClassDefinition\Data\Classificationstore) {
                            $this->classificationStore($definition, $currentObject, $previousObject, $fieldName, $validLanguages);
                        } elseif ($definition instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
                            $this->fieldCollection($currentObject, $previousObject, $fieldName, $validLanguages);
                        } else {
                            $keyData1 = false !== $currentObject->getValueForFieldName($fieldName) ? $currentObject->getValueForFieldName($fieldName) : null;
                            $v1 = $definition->getVersionPreview($keyData1);

                            $keyData2 = false !== $previousObject->getValueForFieldName($fieldName) ? $previousObject->getValueForFieldName($fieldName) : null;
                            $v2 = $definition->getVersionPreview($keyData2);

                            if ($v1 !== $v2) {
                                Simple::log('updateLogger', "$fieldName: " . $v2 . ' -> ' . $v1);
                            }
                        }
                    }
                }
            }
        }
    }

    private function defaultInformation(Version $currentVersion, AbstractObject $currentObject): void
    {
        Simple::log('updateLogger', '==========================================');
        Simple::log('updateLogger', 'object id: ' . $currentVersion->getData()->getId());
        Simple::log('updateLogger', 'object link: ' . Tool::getHostname() . '/admin/login/deeplink?object_' . $currentObject->getId() . '_object');
        Simple::log('updateLogger', 'modification date: ' . date('Y-m-d H:i:s'));
        Simple::log('updateLogger', 'user id: ' . $currentVersion->getUser()->getId());
        Simple::log('updateLogger', 'user email: ' . $currentVersion->getUser()->getEmail());
    }

    private function localizedFields(
        DataObject\ClassDefinition\Data\Localizedfields $definition,
        AbstractObject $currentObject,
        AbstractObject $previousObject,
        string $fieldName,
        array $validLanguages
    ): void {
        foreach ($validLanguages as $language) {
            foreach ($definition->getFieldDefinitions() as $lfd) {
                $v1Container = $currentObject->getValueForFieldName($fieldName);
                $keyData1 = $v1Container ? $v1Container->getLocalizedValue($lfd->getName(), $language) : null;
                $v1 = $lfd->getVersionPreview($keyData1);

                $v2Container = $previousObject->getValueForFieldName($fieldName);
                $keyData2 = $v2Container ? $v2Container->getLocalizedValue($lfd->getName(), $language) : null;
                $v2 = $lfd->getVersionPreview($keyData2);

                if ($v1 !== $v2) {
                    Simple::log('updateLogger', "$fieldName " . $lfd->getName() . " ($language): " . $v2 . ' -> ' . $v1);
                }
            }
        }
    }

    private function objectBricks(
        DataObject\ClassDefinition\Data\Objectbricks $definition,
        AbstractObject $currentObject,
        AbstractObject $previousObject,
        string $fieldName,
        array $validLanguages
    ): void {
        foreach ($definition->getAllowedTypes() as $asAllowedType) {
            $collectionDef = DataObject\Objectbrick\Definition::getByKey($asAllowedType);

            foreach ($collectionDef->getFieldDefinitions() as $lfd) {
                $bricksCurrent = $currentObject->get($fieldName);
                $bricksPrevious = $previousObject->get($fieldName);

                if ($bricksCurrent && $bricksPrevious) {
                    if ($lfd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                        foreach ($validLanguages as $language) {
                            foreach ($lfd->getFieldDefinitions() as $localizedFieldDefinition) {
                                $v1 = null;
                                $v2 = null;
                                $localizedBrickCurrentValue = null;
                                $localizedBrickPreviousValue = null;

                                $brickCurrentValue = $bricksCurrent->get($asAllowedType);

                                if ($brickCurrentValue) {
                                    $localizedBrickValues = $brickCurrentValue->getLocalizedFields();
                                    $localizedBrickCurrentValue = $localizedBrickValues->getLocalizedValue($localizedFieldDefinition->getName(), $language);

                                    if (false !== $localizedBrickCurrentValue) {
                                        $v1 = $localizedFieldDefinition->getVersionPreview($localizedBrickCurrentValue);
                                    } else {
                                        $localizedBrickCurrentValue = null;
                                    }
                                }

                                $brickPreviousValue = $bricksPrevious->get($asAllowedType);

                                if ($brickPreviousValue) {
                                    $localizedBrickValues = $brickPreviousValue->getLocalizedFields();
                                    $localizedBrickPreviousValue = $localizedBrickValues->getLocalizedValue($localizedFieldDefinition->getName(), $language);

                                    if (false !== $localizedBrickPreviousValue) {
                                        $v2 = $localizedFieldDefinition->getVersionPreview($localizedBrickPreviousValue);
                                    } else {
                                        $localizedBrickPreviousValue = null;
                                    }
                                }

                                if ($v1 !== $v2) {
                                    Simple::log('updateLogger', "$fieldName " . $lfd->getName() . ' (' . $language . '): ' . $v2 . ' -> ' . $v1);
                                }
                            }
                        }
                    } else {
                        $v1 = null;
                        $brickCurrentValue = $bricksCurrent->get($asAllowedType);

                        if ($brickCurrentValue) {
                            $brickCurrentValue = $brickCurrentValue->getValueForFieldName($lfd->getName());

                            if (false !== $brickCurrentValue) {
                                $v1 = $lfd->getVersionPreview($brickCurrentValue);
                            } else {
                                $brickCurrentValue = null;
                            }
                        }

                        $v2 = null;
                        $brickPreviousValue = $bricksPrevious->get($asAllowedType);

                        if ($brickPreviousValue) {
                            $brickPreviousValue = $brickPreviousValue->getValueForFieldName($lfd->getName());

                            if (false !== $brickPreviousValue) {
                                $v2 = $lfd->getVersionPreview($brickPreviousValue);
                            } else {
                                $brickPreviousValue = null;
                            }
                        }

                        if ($v1 !== $v2) {
                            Simple::log('updateLogger', "$fieldName " . $lfd->getName() . ': ' . $v2 . ' -> ' . $v1);
                        }
                    }
                }
            }
        }
    }

    private function classificationStore(
        DataObject\ClassDefinition\Data\Classificationstore $definition,
        AbstractObject $currentObject,
        AbstractObject $previousObject,
        string $fieldName,
        array $validLanguages
    ): void {
        $storeDataCurrent = $currentObject->getValueForFieldName($fieldName);
        $storeDataPrevious = $previousObject->getValueForFieldName($fieldName);

        $existingGroups = [];
        $activeGroupsCurrent = [];

        if ($storeDataCurrent) {
            $activeGroupsCurrent = $storeDataCurrent->getActiveGroups();
        }

        $activeGroupsPrevious = [];

        if ($storeDataPrevious) {
            $activeGroupsPrevious = $storeDataPrevious->getActiveGroups();
        }

        $activeGroups = $activeGroupsCurrent + $activeGroupsPrevious;

        foreach ($activeGroups as $activeGroupId => $enabled) {
            $existingGroups = $existingGroups + ["$activeGroupId" => $enabled];
        }

        if ($existingGroups) {
            $languages = ['default'];

            if ($definition->isLocalized()) {
                $languages = $languages + $validLanguages;
            }
        }

        foreach ($activeGroups as $activeGroupId => $enabled) {
            $groupDefinition = GroupConfig::getById($activeGroupId);

            if ($groupDefinition) {
                $keyGroupRelations = $groupDefinition->getRelations();
                $pimcoreObjectExtension = new PimcoreObjectExtension();

                foreach ($keyGroupRelations as $keyGroupRelation) {
                    $keyDef = $pimcoreObjectExtension->getFieldDefinitionFromJson($keyGroupRelation->getDefinition(), $keyGroupRelation->getType());

                    if ($keyDef) {
                        foreach ($languages as $language) {
                            $keyData1 = $storeDataCurrent ? $storeDataCurrent->getLocalizedKeyValue($activeGroupId, $keyGroupRelation->getKeyId(), $language, true, true) : null;
                            $v1 = $keyDef->getVersionPreview($keyData1);
                            $keyData2 = $storeDataPrevious ? $storeDataPrevious->getLocalizedKeyValue($activeGroupId, $keyGroupRelation->getKeyId(), $language, true, true) : null;
                            $v2 = $keyDef->getVersionPreview($keyData2);

                            if ($v1 !== $v2) {
                                Simple::log('updateLogger', "$fieldName " . $keyGroupRelation->getName() . " ($language): " . $v2 . ' -> ' . $v1);
                            }
                        }
                    }
                }
            }
        }
    }

    private function fieldCollection(
        AbstractObject $currentObject,
        AbstractObject $previousObject,
        string $fieldName,
        array $validLanguages
    ): void {
        $currentFields = $currentObject->get($fieldName);
        $previousFields = $previousObject->get($fieldName);

        $currentFieldItems = null;
        $previousFieldItems = null;
        $currentFieldDefinition = null;
        $previousFieldDefinition = null;

        $fieldKeys2 = null;

        $skipFieldItems2 = [];
        $ffkey1 = null;
        $ffkey2 = null;

        if ($currentFields) {
            $currentFieldDefinition = $currentFields->getItemDefinitions();
            $currentFieldItems = $currentFields->getItems();
        }

        if ($previousFields) {
            $previousFieldDefinition = $previousFields->getItemDefinitions();
            $previousFieldItems = $previousFields->getItems();
        }

        if (is_iterable($currentFieldItems) && count($currentFieldItems) > 0) {
            foreach ($currentFieldItems as $fkey1 => $fieldItem1) {
                $fieldKeys1 = $currentFieldDefinition[$fieldItem1->getType()]->getFieldDefinitions();

                if (key_exists($fkey1, $previousFieldItems) && $fieldItem1->getType() == $previousFieldItems[$fkey1]->getType()) {
                    $ffkey2 = $previousFieldItems[$fkey1];
                    $fieldKeys2 = $previousFieldDefinition[$ffkey2->getType()]->getFieldDefinitions();
                    $skipFieldItems2 = array_merge([$fkey1, $fkey1]);
                }

                foreach ($fieldKeys1 as $fkey => $fieldKey1) {
                    $v1 = null;
                    $v2 = null;
                    $keyData2 = null;
                    $keyData1 = $fieldItem1->get($fieldKey1->getName());

                    if ($fieldKey1 instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                        foreach ($validLanguages as $language) {
                            foreach ($fieldKey1->getChildren() as $child) {
                                $currentLocalizedValue = $keyData1->getLocalizedValue($child->getName(), $language);

                                if (false !== $currentLocalizedValue) {
                                    $v1 = $child->getVersionPreview($currentLocalizedValue);
                                } else {
                                    $currentLocalizedValue = null;
                                }

                                if ($ffkey2 && key_exists($fkey, $fieldKeys2)) {
                                    $keyData2 = $ffkey2->get($fieldKeys2[$fkey]->getName());
                                    $previousLocalizedValue = $keyData2->getLocalizedValue($child->getName(), $language);

                                    if (false !== $previousLocalizedValue) {
                                        $v2 = $child->getVersionPreview($previousLocalizedValue);
                                    } else {
                                        $previousLocalizedValue = null;
                                    }
                                }

                                if ($v1 !== $v2) {
                                    Simple::log('updateLogger', $fieldName . $fieldItem1->getIndex() . ' ' . $child->getName() . ' (' . $language . '): ' . $v2 . ' -> ' . $v1);
                                }
                            }
                        }
                    } else {
                        $v1 = $fieldKey1->getVersionPreview($keyData1);

                        if ($ffkey2 && key_exists($fkey, $fieldKeys2)) {
                            $keyData2 = $ffkey2->get($fieldKeys2[$fkey]->getName());
                            $v2 = $fieldKey1->getVersionPreview($keyData2);
                        }

                        if ($v1 !== $v2) {
                            Simple::log('updateLogger', $fieldName . $fieldItem1->getIndex() . ' ' . $fieldKey1->getName() . ': ' . $v2 . ' -> ' . $v1);
                        }
                    }
                }
            }
        }

        if (is_iterable($previousFieldItems) && count($previousFieldItems) > 0) {
            foreach ($previousFieldItems as $fkey2 => $fieldItem2) {
                if (!in_array($fkey2, array_keys($skipFieldItems2), true)) {
                    $fieldKeys1 = null;
                    $fieldKeys2 = $previousFieldDefinition[$fieldItem2->getType()]->getFieldDefinitions();

                    if (key_exists($fkey2, $currentFieldItems) && $fieldItem2->getType() == $currentFieldItems[$fkey2]->getType()) {
                        $ffkey1 = $currentFieldItems[$fkey2];
                        $fieldKeys1 = $currentFieldDefinition[$ffkey1->getType()]->getFieldDefinitions();
                    }

                    foreach ($fieldKeys2 as $fkey => $fieldKey2) {
                        $v1 = null;
                        $keyData1 = null;
                        $keyData2 = $fieldItem2->get($fieldKey2->getName());
                        $v2 = $fieldKey2->getVersionPreview($keyData2);

                        if ($ffkey1 && key_exists($fkey, $fieldKeys1)) {
                            $keyData1 = $ffkey1->get($fieldKeys1[$fkey]->getName());
                            $v1 = $fieldKey2->getVersionPreview($keyData1);
                        }

                        if ($v1 !== $v2) {
                            Simple::log('updateLogger', $fieldName . $fieldItem2->getIndex() . ' ' . $fieldKey2->getName() . ': ' . $v2 . ' -> ' . $v1);
                        }
                    }
                }
            }
        }
    }
}
