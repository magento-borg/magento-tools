<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\Entity;

class ClassMetadata extends AbstractMetadata
{
    /** @var string */
    private $path;
    /** @var MethodMetadata[] */
    private $methods;
    /** @var PropertyMetadata[] */
    private $properties;
    /** @var ConstantMetadata[] */
    private $constants;

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

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

    /**
     * @return ConstantMetadata[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * @param ConstantMetadata[] $constants
     */
    public function setConstants(array $constants)
    {
        $this->constants = $constants;
    }

    public function toArray()
    {
        $output = parent::toArray();
        $output['path'] = $this->path;
        $output['methods'] = array_map(function (MethodMetadata $item) { return $item->toArray(); }, $this->methods);
        $output['properties'] = array_map(function (PropertyMetadata $item) { return $item->toArray(); }, $this->properties);
        $output['constants'] = array_map(function (ConstantMetadata $item) { return $item->toArray(); }, $this->constants);
        return $output;
    }

}
