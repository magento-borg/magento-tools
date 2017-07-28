<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflection\ReflectionProperty;
use BetterReflection\Reflector\ClassReflector;

class DeprecatedProperties extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return 'deprecated.properties';
    }

    /**
     * @param \Zend\Log\Logger $logger
     */
    protected function execute(\Zend\Log\Logger $logger)
    {
        $reflector = $this->getClassReflector();
        $changeLog = $this->getChangeLog();
        $index = 1;
        $count = count($changeLog);
        foreach ($changeLog as $info) {
            $logger->info('Processing ' . $index . ' of ' . $count . '. ' . $info['class'] . "::" . $info['property']);
            if (!$info['deprecatedSince']) {
                try {
                    $this->updateDocBlock($reflector, $info['class'], $info['property'], $info['expectedSince'], $logger);
                } catch (\Exception $exception) {
                    $logger->err('Error in method ' . $info['class'] . "::" . $info['property'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateDocBlock(ClassReflector $reflector, $className, $propertyName, $expectedSince, \Zend\Log\Logger $logger)
    {
        $reflectionClass = $reflector->reflect($className);
        /** @var ReflectionProperty $reflectionProperty */
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
        try {
            $doc = $reflectionProperty->getDocComment();
            $docBlockLines = explode(PHP_EOL, $doc);
            $docBlockLines = array_map('trim', $docBlockLines);
            $fileContent = file(realpath($reflectionClass->getLocatedSource()->getFileName()));
            $trimmedContent = array_map('trim', $fileContent);
            $deprecatedLineIndex = $this->findDeprecationLineIndex($docBlockLines, $trimmedContent, $propertyName);
            if ($deprecatedLineIndex === null) {
                $logger->err('Can not find property ' . $propertyName . ' in ' . $className);
                return;
            }
            $originLine = $fileContent[$deprecatedLineIndex];
            $updatedDocBlockLine = str_replace('@deprecated', '@deprecated ' . $expectedSince, $originLine);
            $fileContent[$deprecatedLineIndex] = $updatedDocBlockLine;
            $newContent = implode('', $fileContent);
            $this->saveContent($reflectionClass, $newContent);
        } catch (\Exception $exception) {
            $logger->err('Invalid docBlock in ' . $className . '::' . $propertyName . PHP_EOL . $exception->getMessage());
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
    private function findDeprecationLineIndex($needle, $haystack, $propertyName)
    {
        $batchSize = count($needle);
        $searchString = implode(PHP_EOL, $needle);
        for ($index = 0; $index < count($haystack); $index++) {
             $part = implode(PHP_EOL, array_slice($haystack, $index, $batchSize));
             if ($part == $searchString) {
                 $propertyNameLine = $haystack[$index + $batchSize];
                 if (strpos($propertyNameLine, $propertyName) !== false) {
                     foreach (array_slice($haystack, $index, $batchSize) as $subIndex => $originDocBlockLines) {
                         if (strpos($originDocBlockLines, '@deprecated') !== false) {
                             return $index + $subIndex;
                         }
                     }
                 }
             }
        }
        return null;
    }
}