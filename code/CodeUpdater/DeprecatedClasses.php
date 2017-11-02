<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflector\ClassReflector;
use Magento\DeprecationTool\AppConfig;

class DeprecatedClasses extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return AppConfig::TYPE_DEPRECATED_CLASSES;
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
        foreach ($changeLog as $classInfo) {
            $logger->info('Processing ' . $index . ' of ' . $count . '. ' . $classInfo['class'] . PHP_EOL);
            if ($classInfo['actualDeprecatedSince'] === "") {
                try {
                    $this->updateClassDocBlock($reflector, $classInfo['class'], $classInfo['expectedDeprecatedSince'], $classInfo['actualDeprecatedSince']);
                } catch (\Exception $exception) {
                    $logger->err('Error in class ' . $classInfo['class'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateClassDocBlock(ClassReflector $reflector, $className, $expected, $actual)
    {
        $reflectionClass = $reflector->reflect($className);
        $doc = $reflectionClass->getDocComment();
        $filePosition = $reflectionClass->getAst()->getDocComment()->getFilePos();
        if ($actual) {
            $updatedDocBlock = str_replace('@deprecated ' . $actual, '@deprecated ' . $expected, $doc);
        } else {
            $updatedDocBlock = str_replace('@deprecated', '@deprecated ' . $expected, $doc);
        }
        $source = $reflectionClass->getLocatedSource()->getSource();
        $sourceBefore = substr($source, 0, $filePosition);
        $sourceAfter = substr($source, $filePosition + strlen($doc));
        $newContent = $sourceBefore . $updatedDocBlock . $sourceAfter;
        $this->saveContent($reflectionClass, $newContent);
    }
}
