<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflector\ClassReflector;

class DeprecatedMethods extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return 'deprecated.methods';
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
            $logger->info('Processing ' . $index . ' of ' . $count . '. ' . $info['class'] . "::" . $info['method']);
            if (!$info['deprecatedSince']) {
                try {
                    $this->updateDocBlock($reflector, $info['class'], $info['method'], $info['expectedSince'], $logger);
                } catch (\Exception $exception) {
                    $logger->err('Error in method ' . $info['class'] . "::" . $info['method'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateDocBlock(ClassReflector $reflector, $className, $methodName, $expectedSince, \Zend\Log\Logger $logger)
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
            $updatedDocBlock = str_replace('@deprecated', '@deprecated ' . $expectedSince, $doc);
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
