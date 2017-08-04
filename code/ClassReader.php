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
     * @return ClassMetadata[]
     */
    public function read($filePath, $autoloaderPath, $package)
    {
        /** @var ClassLoader $classLoader */
        $classLoader = require $autoloaderPath;
        $classLoader->register(true);
        $classes = AnnotationsParser::parsePhp(file_get_contents($filePath));
        $reflector = new ClassReflector(new ComposerSourceLocator($classLoader));
        $output = [];

        foreach ($classes as $className => $someData) {
            $reflectionClass = $reflector->reflect($className);
            $docCommentObject = $reflectionClass->getAst()->getDocComment();
            $classDocBlock = new DocBlock($docCommentObject ? $docCommentObject->getText() : '');
            $classDeprecated = $classDocBlock->getTagsByName('deprecated');
            $classSee = $classDocBlock->getTagsByName('see');
            $classSince = $classDocBlock->getTagsByName('since');

            $methods = $this->readMethods($reflectionClass, $package);
            $properties = $this->readProperties($reflectionClass, $package);

            $class = new ClassMetadata();
            $class->setName($reflectionClass->getName());
            $class->setRelease($package['release']);
            $class->setPackage($package['name']);
            $class->setPackageVersion($package['version']);
            $class->setMethods($methods);
            $class->setProperties($properties);
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
     * @param ReflectionClass $reflectionClass
     * @param $package
     * @return array
     */
    private function readMethods(ReflectionClass $reflectionClass, $package)
    {
        $methods = [];
        foreach ($reflectionClass->getImmediateMethods() as $method) {
            $deprecated = $see = $since =[];
            try {
                $docCommentObject = $method->getAst()->getDocComment();
                $docBlock = new DocBlock($docCommentObject ? $docCommentObject->getText() : '');
                $deprecated = $docBlock->getTagsByName('deprecated');
                $see = $docBlock->getTagsByName('see');
                $since = $docBlock->getTagsByName('since');
            } catch (\Exception $exception) {
                echo 'Invalid DocBlock in ' . $reflectionClass->getName() . '::' . $method->getName() . ' ' . realpath($reflectionClass->getLocatedSource()->getFileName()) . ' :: ' . $exception->getMessage() . PHP_EOL;
            }

            $methodMeta = new MethodMetadata();
            $methodMeta->setName($method->getName());
            $methodMeta->setRelease($package['release']);
            $methodMeta->setPackage($package['name']);
            $methodMeta->setPackageVersion($package['version']);
            $methodMeta->setIsDeprecated(!empty($deprecated));
            $methodMeta->setHasSee(!empty($see));
            $methodMeta->setSince($this->getSince($since));
            $methodMeta->setDeprecatedSince($this->getDeprecatedSince($deprecated));

            $methods[$method->getName()] = $methodMeta;
        }
        return $methods;
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param $package
     * @return array
     */
    private function readProperties(ReflectionClass $reflectionClass, $package)
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
                echo 'Invalid DocBlock in ' . $reflectionClass->getName() . '::' . $property->getName() . ' ' . realpath($reflectionClass->getLocatedSource()->getFileName()) . ' :: ' . $exception->getMessage() . PHP_EOL;
            }

            $propertyMetadata = new PropertyMetadata();
            $propertyMetadata->setName($property->getName());
            $propertyMetadata->setIsDeprecated(!empty($deprecated));
            $propertyMetadata->setRelease($package['release']);
            $propertyMetadata->setPackage($package['name']);
            $propertyMetadata->setPackageVersion($package['version']);
            $propertyMetadata->setHasSee(!empty($see));
            $propertyMetadata->setSince($this->getSince($since));
            $propertyMetadata->setDeprecatedSince($this->getDeprecatedSince($deprecated));

            $properties[$property->getName()] = $propertyMetadata;
        }
        return $properties;
    }
}
