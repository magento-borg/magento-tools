<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflector\ClassReflector;
use Magento\DeprecationTool\AppConfig;

class DeprecatedMethods extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return AppConfig::TYPE_DEPRECATED_METHODS;
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
            $logger->info('Processing ' . $index . ' of ' . $count . '. ' . $info['class'] . "::" . $info['method'] . PHP_EOL);
            if ($info['actualDeprecatedSince'] === "") {
                try {
                    $this->updateDocBlock($reflector, $info['class'], $info['method'], $info['expectedDeprecatedSince'], $info['actualDeprecatedSince'], $logger);
                } catch (\Exception $exception) {
                    $logger->err('Error in method ' . $info['class'] . "::" . $info['method'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateDocBlock(ClassReflector $reflector, $className, $methodName, $expected, $actual, \Zend_Log $logger)
    {
        $reflectionClass = $reflector->reflect($className);
        $reflectionMethod = null;
        $methods = $reflectionClass->getImmediateMethods();
        foreach ($methods as $method) {
            if ($method->getName() == $methodName) {
                $reflectionMethod = $method;
                break;
            }
        }
        if (!$reflectionMethod) {
            $logger->err('Method not found ' . $className . '::' . $methodName);
            return;
        }
        try {
            $doc = $reflectionMethod->getAst()->getDocComment()->getText();
            $filePosition = $reflectionMethod->getAst()->getDocComment()->getFilePos();
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
        } catch (\Exception $exception) {
            $logger->err('Invalid docBlock in ' . $className . '::' . $methodName . PHP_EOL . $exception->getMessage());
        }
    }
}
