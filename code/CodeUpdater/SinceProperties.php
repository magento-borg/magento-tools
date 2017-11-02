<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflector\ClassReflector;
use Magento\DeprecationTool\AppConfig;

class SinceProperties extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return AppConfig::TYPE_SINCE_PROPERTIES;
    }

    /**
     * @param \Zend_Log $logger
     */
    protected function execute(\Zend_Log $logger)
    {
        $reflector = $this->getClassReflector();
        $changeLog = $this->getChangeLog();
        $index = 1;
        $count = count($changeLog);
        foreach ($changeLog as $info) {
            $logger->info('Processing ' . $index . ' of ' . $count . '. ' . $info['class'] . "::" . $info['property'] . PHP_EOL);
            if ($info['actualCreatedSince'] === "") {
                try {
                    $this->updateClassDocBlock($reflector, $info['class'], $info['property'], $info['expectedCreatedSince'], $info['actualCreatedSince'], $logger);
                } catch (\Exception $exception) {
                    $logger->err('Error in method ' . $info['class'] . "::" . $info['property'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateClassDocBlock(ClassReflector $reflector, $className, $propertyName, $expected, $actual, \Zend_Log $logger)
    {
        $reflectionClass = $reflector->reflect($className);

        $reflectionProperty = null;
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property) {
            if ($property->getName() == $propertyName && $property->getDeclaringClass()->getName() == $reflectionClass->getName()) {
                $reflectionProperty = $property;
                break;
            }
        }
        if (!$reflectionProperty) {
            $logger->err('Property not found ' . $className . '::' . $propertyName);
            return;
        }

        $doc = $reflectionProperty->getDocComment();
        if (empty($doc)) {
            $logger->err("No doc block for $className::$propertyName");
        } else {
            $docBlockLines = explode(PHP_EOL, $doc);
            $docBlockLines = array_map('trim', $docBlockLines);
            $fileContent = file(realpath($reflectionClass->getLocatedSource()->getFileName()));
            $trimmedContent = array_map('trim', $fileContent);
            $propertyLineIndex = $this->findPropertyLineIndex($docBlockLines, $trimmedContent, $propertyName);
            if ($propertyLineIndex === null) {

                //fallback to base reflection when better reflection fails.
                $baseReflection = new \ReflectionProperty($reflectionClass->getName(), $reflectionProperty->getName());
                $origin = $baseReflection->getDocComment();

                $docBlockLines = explode(PHP_EOL, $origin);
                $docBlockLines = array_map('trim', $docBlockLines);
                $propertyLineIndex = $this->findPropertyLineIndex($docBlockLines, $trimmedContent, $propertyName);

                if ($propertyLineIndex === null) {
                    $logger->err('Can not find property $' . $propertyName . ' in ' . $className);
                    return;
                }
            }

            if ($actual) {
                if ($expected) {
                    $updatedDocBlock = str_replace('@since ' . $actual, '@since ' . $expected, $doc);
                } else {
                    $updatedDocBlock = str_replace(' * @since ' . $actual . PHP_EOL, '', $doc);
                }
                $docLines = explode(PHP_EOL, $updatedDocBlock);
                $docLines = array_map(function ($item) { return '    ' . $item; }, $docLines);
                $updatedDocBlock = implode(PHP_EOL, $docLines) . PHP_EOL;
                $after = array_slice($fileContent, $propertyLineIndex);
                $before = array_slice($fileContent, 0, $propertyLineIndex - count($docBlockLines));
                $newContent = implode('', array_merge($before, [$updatedDocBlock], $after));
            } else {
                if (count($docBlockLines) == 1) {
                    $before = array_slice($fileContent, 0, $propertyLineIndex - 1);
                    $after = array_slice($fileContent, $propertyLineIndex);
                    $line = [];
                    $line[] = '    /**';
                    $line[] = '     * @var ' . implode('|', $reflectionProperty->getDocBlockTypeStrings());
                    $line[] = '     * @since ' . $expected;
                    $line[] = '     */' . PHP_EOL;
                    $newContent = implode('', array_merge($before, [implode(PHP_EOL, $line)], $after));
                } else {
                    $after = array_slice($fileContent, $propertyLineIndex - 1);
                    $before = array_slice($fileContent, 0, $propertyLineIndex - 1);
                    $line = '     * @since ' . $expected . PHP_EOL;
                    $newContent = implode('', array_merge($before, [$line], $after));
                }
            }
            $this->saveContent($reflectionClass, $newContent);
        }
    }

    /**
     * Find the line where deprecated tag is declared for the property.
     *
     * @param $needle
     * @param $haystack
     * @param $propertyName
     * @return int|null
     */
    private function findPropertyLineIndex($needle, $haystack, $propertyName)
    {
        $batchSize = count($needle);
        $searchString = implode(PHP_EOL, $needle);
        for ($index = 0; $index < count($haystack); $index++) {
            $part = implode(PHP_EOL, array_slice($haystack, $index, $batchSize));
            if ($part == $searchString) {
                $propertyNameLine = $haystack[$index + $batchSize];
                if (strpos('$' . $propertyNameLine . ' ', $propertyName) !== false
                    || strpos('$' . $propertyNameLine . ';', $propertyName) !== false) {
                    return $index + $batchSize;
                }
            }
        }
        return null;
    }
}
