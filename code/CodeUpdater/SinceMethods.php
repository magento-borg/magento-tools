<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflector\ClassReflector;

class SinceMethods extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return 'new.methods';
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
            if (!$info['createdSince']) {
                try {
                    $this->updateClassDocBlock($reflector, $info['class'], $info['method'], $info['expectedSince'], $logger);
                } catch (\Exception $exception) {
                    $logger->err('Error in method ' . $info['class'] . "::" . $info['method'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateClassDocBlock(ClassReflector $reflector, $className, $methodName, $since, \Zend\Log\Logger $logger)
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

        $docBlock = $reflectionMethod->getAst()->getDocComment();
        $startLine = $reflectionMethod->getStartLine();
        $lines = file(realpath($reflectionClass->getLocatedSource()->getFileName()));
        $after = array_slice($lines, $startLine - 1);
        if (!$docBlock) {
            $before = array_slice($lines, 0, $startLine - 1);
            $update = [
                '    /**' . PHP_EOL
                . '     * @since ' . $since . PHP_EOL
                . '     */' . PHP_EOL,
            ];
            $newContent = implode('', array_merge($before, $update, $after));
        } else {
            $doc = $docBlock->getText();
            $linesInDocBlock = count(explode(PHP_EOL, $doc));
            $before = array_slice($lines, 0, $startLine - 1 - $linesInDocBlock);
            $lines = explode(PHP_EOL, $doc);
            $closingLine = array_pop($lines);
            $lines[] = '     * @since ' . $since;
            $lines[] = $closingLine . PHP_EOL;
            $updatedDocBlock = '    ' . implode(PHP_EOL, $lines);
            $newContent = implode('', array_merge($before, [$updatedDocBlock], $after));
        }
        $this->saveContent($reflectionClass, $newContent);
    }
}
