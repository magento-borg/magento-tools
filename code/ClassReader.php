<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool;
use BetterReflection\Reflection\ReflectionClass;
use Composer\Autoload\ClassLoader;
use Magento\DeprecationTool\Entity\AbstractMetadata;
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
     * @param $filePath
     * @param $autoloaderPath
     * @param $package
     * @param \Zend_Log $logger
     * @return AbstractMetadata[]
     */
    public function read($filePath, $autoloaderPath, $package, \Zend_Log $logger)
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
            $api = $classDocBlock->getTagsByName('api');
            $isAPI = !empty($api);

            $methods = $this->readMethods($reflectionClass, $package, $isAPI, $logger);
            $properties = $this->readProperties($reflectionClass, $package, $isAPI, $logger);

            $class = new ClassMetadata();
            $class->setName($reflectionClass->getName());
            $class->setRelease($package['release']);
            $class->setPackage($package['name']);
            $class->setPackageVersion($package['version']);
            $class->setMethods($methods);
            $class->setProperties($properties);
            $class->setIsDeprecated(!empty($classDeprecated));
            $class->setIsPrivate(false);
            $class->setIsApi($isAPI);
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
     * @param $isApi
     * @return array
     */
    private function readMethods(ReflectionClass $reflectionClass, $package, $isApi, \Zend_Log $logger)
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
                $logger->err('Invalid DocBlock in ' . $reflectionClass->getName() . '::' . $method->getName() . ' ' . realpath($reflectionClass->getLocatedSource()->getFileName()) . ' :: ' . $exception->getMessage());
            }

            $methodMeta = new MethodMetadata();
            $methodMeta->setName($method->getName());
            $methodMeta->setRelease($package['release']);
            $methodMeta->setPackage($package['name']);
            $methodMeta->setPackageVersion($package['version']);
            $methodMeta->setIsDeprecated(!empty($deprecated));
            $methodMeta->setIsApi($isApi);
            $methodMeta->setIsPrivate($method->isPrivate());
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
     * @param $isApi
     * @return array
     */
    private function readProperties(ReflectionClass $reflectionClass, $package, $isApi, \Zend_Log $logger)
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
                $logger->err('Invalid DocBlock in ' . $reflectionClass->getName() . '::' . $property->getName() . ' ' . realpath($reflectionClass->getLocatedSource()->getFileName()) . ' :: ' . $exception->getMessage());
            }

            $propertyMetadata = new PropertyMetadata();
            $propertyMetadata->setName($property->getName());
            $propertyMetadata->setIsDeprecated(!empty($deprecated));
            $propertyMetadata->setRelease($package['release']);
            $propertyMetadata->setPackage($package['name']);
            $propertyMetadata->setPackageVersion($package['version']);
            $propertyMetadata->setHasSee(!empty($see));
            $propertyMetadata->setIsApi($isApi);
            $propertyMetadata->setIsPrivate($property->isPrivate());
            $propertyMetadata->setSince($this->getSince($since));
            $propertyMetadata->setDeprecatedSince($this->getDeprecatedSince($deprecated));

            $properties[$property->getName()] = $propertyMetadata;
        }
        return $properties;
    }
}
