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
            if ($isDeprecated && !$classData['deprecatedSince']) {
                $requiredDeprecatedSince = $dataStructure->getSinceInformation($path);
                $deprecatedClasses[] = [
                    'entity' => $className,
                    'expectedSince' => $requiredDeprecatedSince,
                    'deprecatedSince' => $classData['deprecatedSince'],
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
                if ($isDeprecated && !$methodData['deprecatedSince']) {
                    $requiredDeprecatedSince = $dataStructure->getSinceInformation($className . '/methods/' . $methodName . '/isDeprecated');
                    $deprecatedMethods[] = [
                        'entity' => $className . '::' . $methodName,
                        'expectedSince' => $requiredDeprecatedSince,
                        'deprecatedSince' => $methodData['deprecatedSince'],
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
                if ($isDeprecated && !$propertyData['deprecatedSince']) {
                    $requiredDeprecatedSince = $dataStructure->getSinceInformation($className . '/properties/' . $propertyName . '/isDeprecated');
                    $output[] = [
                        'entity' => $className . '::' . $propertyName,
                        'expectedSince' => $requiredDeprecatedSince,
                        'deprecatedSince' => $propertyData['deprecatedSince'],
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
            $requiredCreatedSince = $dataStructure->getSinceInformation($className);
            if ($classData['since']) {
                continue;
            }
            $classes[] = [
                'entity' => $className,
                'createdSince' => $classData['since'],
                'expectedSince' => $requiredCreatedSince,
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
                if ($methodData['since']) {
                    continue;
                }
                $requiredCreatedSince = $dataStructure->getSinceInformation($className . '/methods/' . $methodName);
                $output[] = [
                    'entity' => $className . '::' . $methodName,
                    'expectedSince' => $requiredCreatedSince,
                    'createdSince' => $methodData['since'],
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
                if ($data['since']) {
                    continue;
                }
                $requiredCreatedSince = $dataStructure->getSinceInformation($className . '/properties/' . $name);
                $output[] = [
                    'entity' => $className . '::' . $name,
                    'expectedSince' => $requiredCreatedSince,
                    'createdSince' => $data['since'],
                    'property' => $name,
                    'class' => $className
                ];

            }
        }
        return $output;
    }
}
