<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use Magento\Framework\App\Cache\Type\Reflection;

class SinceClasses extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return 'new.classes';
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
            if ($classInfo['expectedSince'] != $classInfo['createdSince']) {
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
        $classStart = $reflectionClass->getAst()->getLine();
        $docBlock = $reflectionClass->getAst()->getDocComment();
        if ($docBlock) {
            $doc = $docBlock->getText();
            $lines = explode(PHP_EOL, $doc);
            $filePosition = $reflectionClass->getAst()->getDocComment()->getFilePos();
            if (count($lines) == 1) {
                $comment = str_replace('/**', '', $doc);
                $comment = trim(str_replace('*/', '', $comment));

                $lines = file(realpath($reflectionClass->getLocatedSource()->getFileName()));
                $before = array_slice($lines, 0, $classStart - 2);
                $after = array_slice($lines, $classStart - 1);
                $update = [
                    '/**' . PHP_EOL
                    . ' * ' . $comment . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * @since ' . $expectedSince . PHP_EOL
                    . ' */' . PHP_EOL,
                ];
                $newContent = implode('', array_merge($before, $update, $after));
            } else {
                $closingLine = array_pop($lines);
                $lines[] = ' * @since ' . $expectedSince;
                $lines[] = $closingLine;
                $updatedDocBlock = implode(PHP_EOL, $lines);
                $source = $reflectionClass->getLocatedSource()->getSource();
                $sourceBefore = substr($source, 0, $filePosition);
                $sourceAfter = substr($source, $filePosition + strlen($doc));
                $newContent = $sourceBefore . $updatedDocBlock . $sourceAfter;
            }

        } else {
            $lines = file(realpath($reflectionClass->getLocatedSource()->getFileName()));
            $before = array_slice($lines, 0, $classStart - 1);
            $after = array_slice($lines, $classStart - 1);
            $update = [
                '/**' . PHP_EOL
                . ' * ' . $this->getEntityType($reflectionClass) . ' \\' . $reflectionClass->getName() . PHP_EOL
                . ' *' . PHP_EOL
                . ' * @since ' . $expectedSince . PHP_EOL
                . ' */' . PHP_EOL,
            ];
            $newContent = implode('', array_merge($before, $update, $after));
        }
        $this->saveContent($reflectionClass, $newContent);
    }

    private function getEntityType(ReflectionClass $reflectionClass)
    {
        if ($reflectionClass->isInterface()) {
            return 'Interface';
        }
        return 'Class';
    }
}
