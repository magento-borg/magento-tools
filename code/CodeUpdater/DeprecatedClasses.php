<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflector\ClassReflector;

class DeprecatedClasses extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return 'deprecated.classes';
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
        foreach ($changeLog as $classInfo) {
            $logger->info('Processing ' . $index . ' of ' . $count . '. ' . $classInfo['class']);
            if (!$classInfo['deprecatedSince']) {
                try {
                    $this->updateClassDocBlock($reflector, $classInfo['class'], $classInfo['expectedSince']);
                } catch (\Exception $exception) {
                    $logger->err('Error in class ' . $classInfo['class'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateClassDocBlock(ClassReflector $reflector, $className, $expectedSince)
    {
        $reflectionClass = $reflector->reflect($className);
        $doc = $reflectionClass->getDocComment();
        $filePosition = $reflectionClass->getAst()->getDocComment()->getFilePos();
        $updatedDocBlock = str_replace('@deprecated', '@deprecated ' . $expectedSince, $doc);
        $source = $reflectionClass->getLocatedSource()->getSource();
        $sourceBefore = substr($source, 0, $filePosition);
        $sourceAfter = substr($source, $filePosition + strlen($doc));
        $newContent = $sourceBefore . $updatedDocBlock . $sourceAfter;
        $this->saveContent($reflectionClass, $newContent);
    }
}
