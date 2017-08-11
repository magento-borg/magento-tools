<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflector\ClassReflector;
use Magento\DeprecationTool\AppConfig;
use Magento\Framework\App\Cache\Type\Reflection;

class SinceClasses extends AbstractUpdater
{
    /**
     * @return string
     */
    protected function getLogType()
    {
        return AppConfig::TYPE_SINCE_CLASSES;
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
            $logger->info('Processing ' . $index . ' of ' . $count . '. ' . $info['class'] . PHP_EOL);
            if ($info['actualCreatedSince'] != $info['expectedCreatedSince']) {
                try {
                    $this->updateClassDocBlock($reflector, $info['class'], $info['expectedCreatedSince'], $info['actualCreatedSince']);
                } catch (\Exception $exception) {
                    $logger->err('Error in class ' . $info['class'] .  PHP_EOL . $exception->getMessage());
                }
            }
            $index++;
        }
    }

    private function updateClassDocBlock(ClassReflector $reflector, $className, $expected, $actual)
    {
        $reflectionClass = $reflector->reflect($className);
        $classStart = $reflectionClass->getAst()->getLine();
        $docBlock = $reflectionClass->getAst()->getDocComment();
        if ($docBlock) {
            $doc = $docBlock->getText();
            $filePosition = $reflectionClass->getAst()->getDocComment()->getFilePos();
            if ($actual) {
                if ($expected) {
                    $updatedDocBlock = str_replace('@since ' . $actual, '@since ' . $expected, $doc);
                } else {
                    $updatedDocBlock = str_replace(' * @since ' . $actual . PHP_EOL, '', $doc);
                }
                $source = $reflectionClass->getLocatedSource()->getSource();
                $sourceBefore = substr($source, 0, $filePosition);
                $sourceAfter = substr($source, $filePosition + strlen($doc));
                $newContent = $sourceBefore . $updatedDocBlock . $sourceAfter;

            } else {
                $lines = explode(PHP_EOL, $doc);
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
                        . ' * @since ' . $expected . PHP_EOL
                        . ' */' . PHP_EOL,
                    ];
                    $newContent = implode('', array_merge($before, $update, $after));
                } else {
                    $closingLine = array_pop($lines);
                    $lines[] = ' * @since ' . $expected;
                    $lines[] = $closingLine;
                    $updatedDocBlock = implode(PHP_EOL, $lines);
                    $source = $reflectionClass->getLocatedSource()->getSource();
                    $sourceBefore = substr($source, 0, $filePosition);
                    $sourceAfter = substr($source, $filePosition + strlen($doc));
                    $newContent = $sourceBefore . $updatedDocBlock . $sourceAfter;
                }
            }

        } else {
            $lines = file(realpath($reflectionClass->getLocatedSource()->getFileName()));
            $before = array_slice($lines, 0, $classStart - 1);
            $after = array_slice($lines, $classStart - 1);
            $update = [
                '/**' . PHP_EOL
                . ' * @since ' . $expected . PHP_EOL
                . ' */' . PHP_EOL,
            ];
            $newContent = implode('', array_merge($before, $update, $after));
        }
        $this->saveContent($reflectionClass, $newContent);
    }
}
