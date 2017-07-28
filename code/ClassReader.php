<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool;
use BetterReflection\Reflection\ReflectionClass;
use Composer\Autoload\ClassLoader;
use Magento\DeprecationTool\Entity\ClassMetadata;
use Magento\DeprecationTool\Entity\ConstantMetadata;
use Magento\DeprecationTool\Entity\MethodMetadata;
use Magento\DeprecationTool\Entity\PropertyMetadata;
use Nette\Reflection\AnnotationsParser;
use \BetterReflection\Reflector\ClassReflector;
use \BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use \phpDocumentor\Reflection\DocBlock;

class ClassReader
{
    /**
     * @param string $filePath
     * @param string $autoloaderPath
     * @param string $basePath
     * @return ClassMetadata[]
     */
    public function read($filePath, $autoloaderPath, $basePath)
    {
        /** @var ClassLoader $classLoader */
        $classLoader = require $autoloaderPath;
        $classLoader->register();
        $classes = AnnotationsParser::parsePhp(file_get_contents($filePath));
        $reflector = new ClassReflector(new ComposerSourceLocator($classLoader));
        $output = [];

        foreach ($classes as $className => $someData) {
            $reflectionClass = $reflector->reflect($className);
            $classDocBlock = new DocBlock($reflectionClass->getAst()->getDocComment()->getText());
            $classDeprecated = $classDocBlock->getTagsByName('deprecated');
            $classSee = $classDocBlock->getTagsByName('see');
            $classSince = $classDocBlock->getTagsByName('since');

            $methods = $this->readMethods($reflectionClass);
            $properties = $this->readProperties($reflectionClass);
            $constants = $this->readConstants($reflectionClass);

            $class = new ClassMetadata();
            $class->setName($reflectionClass->getName());
            $class->setPath(str_replace($basePath, '', $filePath));
            $class->setMethods($methods);
            $class->setProperties($properties);
            $class->setConstants($constants);
            $class->setIsDeprecated(!empty($classDeprecated));
            $class->setDeprecatedSince($this->getDeprecatedSince($classDeprecated));
            $class->setHasSee(!empty($classSee));
            $class->setSince($this->getSince($classSince));

            $output[] = $class;
        }
        $classLoader->unregister();
        return $output;
    }

    /**
     * @param DocBlock\Tag\DeprecatedTag[] $tags
     * @return null|string
     */
    private function getDeprecatedSince(array $tags)
    {
        if (empty($tags)) {
            return null;
        }
        /** @var DocBlock\Tag\DeprecatedTag $tag */
        $tag = current($tags);
        $version = $tag->getVersion();
        return $version;
    }

    /**
     * @param DocBlock\Tag\SinceTag[] $tags
     * @return null|string
     */
    private function getSince(array $tags)
    {
        if (empty($tags)) {
            return null;
        }
        /** @var DocBlock\Tag\SinceTag $tag */
        $tag = current($tags);
        $version = $tag->getVersion();
        return $version;
    }

    /**
     * @param $reflectionClass
     * @return MethodMetadata[]
     */
    private function readMethods(ReflectionClass $reflectionClass)
    {
        $methods = [];
        foreach ($reflectionClass->getImmediateMethods() as $method) {
            $deprecated = $see = $since =[];
            try {
                $docBlock = new DocBlock($method->getAst()->getDocComment()->getText());
                $deprecated = $docBlock->getTagsByName('deprecated');
                $see = $docBlock->getTagsByName('see');
                $since = $docBlock->getTagsByName('since');
            } catch (\Exception $exception) {
                echo 'Invalid DocBlock in ' . $reflectionClass->getName() . '::' . $method->getName() . ' ' . $reflectionClass->getLocatedSource()->getFileName() . PHP_EOL;
            }

            $methodMeta = new MethodMetadata();
            $methodMeta->setName($method->getName());
            $methodMeta->setIsDeprecated(!empty($deprecated));
            $methodMeta->setHasSee(!empty($see));
            $methodMeta->setSince($this->getSince($since));
            $methodMeta->setDeprecatedSince($this->getDeprecatedSince($deprecated));

            $methods[$method->getName()] = $methodMeta;
        }
        return $methods;
    }

    /**
     * @param $reflectionClass
     * @return PropertyMetadata[]
     */
    private function readProperties(ReflectionClass $reflectionClass)
    {
        $properties = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $deprecated = $see = $since = [];
            try {
                if ($property->getDeclaringClass()->getName() != $reflectionClass->getName()) {
                    //take only immediate properties
                    continue;
                }
                $originProperty = new \ReflectionProperty($reflectionClass->getName(), $property->getName());
                $docBlock = new DocBlock($originProperty->getDocComment());
                $deprecated = $docBlock->getTagsByName('deprecated');
                $see = $docBlock->getTagsByName('see');
                $since = $docBlock->getTagsByName('since');
            } catch (\Exception $exception) {
                echo 'Invalid DocBlock in ' . $reflectionClass->getName() . '::' . $property->getName() . ' ' . $reflectionClass->getLocatedSource()->getFileName() . PHP_EOL;
            }

            $propertyMetadata = new PropertyMetadata();
            $propertyMetadata->setName($property->getName());
            $propertyMetadata->setIsDeprecated(!empty($deprecated));
            $propertyMetadata->setHasSee(!empty($see));
            $propertyMetadata->setSince($this->getSince($since));
            $propertyMetadata->setDeprecatedSince($this->getDeprecatedSince($deprecated));

            $properties[$property->getName()] = $propertyMetadata;
        }
        return $properties;
    }

    /**
     * @param $reflectionClass
     * @return ConstantMetadata[]
     */
    private function readConstants(ReflectionClass $reflectionClass)
    {
        //TODO implement constants read
        return [];
    }
}
