<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\Entity;

class ClassMetadata extends AbstractMetadata
{
    /** @var MethodMetadata[] */
    private $methods;
    /** @var PropertyMetadata[] */
    private $properties;

    /**
     * @return MethodMetadata[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param MethodMetadata[] $methods
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;
    }

    /**
     * @return PropertyMetadata[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param PropertyMetadata[] $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    public function toArray()
    {
        $output = parent::toArray();
        $output['methods'] = array_map(function (MethodMetadata $item) { return $item->toArray(); }, $this->methods);
        $output['properties'] = array_map(function (PropertyMetadata $item) { return $item->toArray(); }, $this->properties);
        return $output;
    }

}
