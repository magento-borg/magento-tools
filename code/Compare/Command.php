<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Compare;

use \Magento\DeprecationTool\DataStructure;

/**
 * Class Command
 * @package Magento\DeprecationTool\Compare
 */
class Command
{
    /**
     * @param DataStructure $dataStructure
     * @return array
     */
    public function compareDeprecatedClasses(DataStructure $dataStructure)
    {
        $deprecatedClasses = [];
        foreach ($dataStructure->getData() as $className => $classData) {
            $path = $className . '/isDeprecated';
            $isDeprecated = $dataStructure->getData($path);
            $requiredDeprecatedSince = $dataStructure->getSinceInformation($path);
            if ($isDeprecated && $classData['deprecatedSince'] != $requiredDeprecatedSince) {
                $deprecatedClasses[] = [
                    'entity' => $className,
                    'expectedDeprecatedSince' => $requiredDeprecatedSince,
                    'actualDeprecatedSince' => $classData['deprecatedSince'],
                    'class' => $className
                ];
            }
        }
        return $deprecatedClasses;
    }

    /**
     * @param DataStructure $dataStructure
     * @return array
     */
    public function compareDeprecatedMethods(DataStructure $dataStructure)
    {
        $deprecatedMethods = [];
        foreach ($dataStructure->getData() as $className => $classData) {
            $methods = $dataStructure->getData($className . '/methods') ?? [];
            foreach ($methods as $methodName => $methodData) {
                $isDeprecated = $methodData['isDeprecated'];
                $requiredDeprecatedSince = $dataStructure->getSinceInformation($className . '/methods/' . $methodName . '/isDeprecated');
                if ($isDeprecated && $methodData['deprecatedSince'] != $requiredDeprecatedSince) {
                    $deprecatedMethods[] = [
                        'entity' => $className . '::' . $methodName,
                        'expectedDeprecatedSince' => $requiredDeprecatedSince,
                        'actualDeprecatedSince' => $methodData['deprecatedSince'],
                        'method' => $methodName,
                        'class' => $className
                    ];
                }
            }
        }
        return $deprecatedMethods;
    }

    /**
     * @param DataStructure $dataStructure
     * @return array
     */
    public function compareDeprecatedProperties(DataStructure $dataStructure)
    {
        $output = [];
        foreach ($dataStructure->getData() as $className => $classData) {
            $properties = $dataStructure->getData($className . '/properties') ?? [];
            foreach ($properties as $propertyName => $propertyData) {
                $isDeprecated = $propertyData['isDeprecated'];
                $requiredDeprecatedSince = $dataStructure->getSinceInformation($className . '/properties/' . $propertyName . '/isDeprecated');
                if ($isDeprecated && $propertyData['deprecatedSince'] != $requiredDeprecatedSince) {
                    $output[] = [
                        'entity' => $className . '::' . $propertyName,
                        'expectedDeprecatedSince' => $requiredDeprecatedSince,
                        'actualDeprecatedSince' => $propertyData['deprecatedSince'],
                        'property' => $propertyName,
                        'class' => $className
                    ];
                }
            }
        }
        return $output;
    }

    /**
     * @param DataStructure $dataStructure
     * @return array
     */
    public function compareNewClasses(DataStructure $dataStructure)
    {
        $classes = [];
        foreach ($dataStructure->getData() as $className => $classData) {
            $requiredCreatedSince = $dataStructure->getCreatedSinceInformation($className);
            if ($classData['since'] == $requiredCreatedSince) {
                continue;
            }
            $classes[] = [
                'entity' => $className,
                'actualCreatedSince' => $classData['since'],
                'expectedCreatedSince' => $requiredCreatedSince,
                'class' => $className
            ];

        }
        return $classes;
    }

    /**
     * @param DataStructure $dataStructure
     * @return array|mixed|null
     */
    public function compareNewMethods(DataStructure $dataStructure)
    {
        $output = [];
        foreach ($dataStructure->getData() as $className => $classData) {
            $methods = $dataStructure->getData($className . '/methods') ?? [];
            foreach ($methods as $methodName => $methodData) {
                $requiredCreatedSince = $dataStructure->getCreatedSinceInformation($className . '/methods/' . $methodName);
                if ($methodData['since'] == $requiredCreatedSince) {
                    continue;
                }
                $output[] = [
                    'entity' => $className . '::' . $methodName,
                    'expectedCreatedSince' => $requiredCreatedSince,
                    'actualCreatedSince' => $methodData['since'],
                    'method' => $methodName,
                    'class' => $className
                ];

            }
        }
        return $output;
    }

    /**
     * @param DataStructure $dataStructure
     * @return array|mixed|null
     */
    public function compareNewProperties(DataStructure $dataStructure)
    {
        $output = [];
        foreach ($dataStructure->getData() as $className => $classData) {
            $properties = $dataStructure->getData($className . '/properties') ?? [];
            foreach ($properties as $name => $data) {
                $requiredCreatedSince = $dataStructure->getCreatedSinceInformation($className . '/properties/' . $name);
                if ($data['since'] == $requiredCreatedSince) {
                    continue;
                }
                $output[] = [
                    'entity' => $className . '::' . $name,
                    'expectedCreatedSince' => $requiredCreatedSince,
                    'actualCreatedSince' => $data['since'],
                    'property' => $name,
                    'class' => $className
                ];

            }
        }
        return $output;
    }
}
