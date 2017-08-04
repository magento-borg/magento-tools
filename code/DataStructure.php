<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool;

class DataStructure
{
    private $baseReleases = [
        AppConfig::CE_EDITION => '2.0.0',
        AppConfig::EE_EDITION => '2.0.0',
        AppConfig::B2B_EDITION => '1.0.0',
    ];
    /**
     * @var array
     */
    private $data;

    /**
     * @var DataStructure
     */
    private $previous;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $edition;

    /**
     * @var string
     */
    private $package;

    /**
     * Initialize dependencies.
     *
     * @param array $data
     * @param $version
     * @param $edition
     */
    public function __construct(array $data, $version, $package, $edition)
    {
        $this->data = $data;
        $this->version = $version;
        $this->edition = $edition;
        $this->package = $package;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return DataStructure
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * @param DataStructure $previous
     * @return $this
     */
    public function setPrevious(DataStructure $previous)
    {
        $this->previous = $previous;
        return $this;
    }

    /**
     * @param $path
     * @return string
     */
    public function getSinceInformation($path)
    {
        if (!$this->getPrevious() || !$this->getPrevious()->getData($path)) {
            return $this->getVersion();
        }
        return $this->getPrevious()->getSinceInformation($path);
    }

    /**
     * @param $path
     * @return string
     */
    public function getCreatedSinceInformation($path)
    {
        if (!$this->getPrevious() || !$this->getPrevious()->getData($path)) {
            $version = $this->getVersion();
            $release = $this->getData($path . '/release');
            $isAPI = $this->getData($path . '/api');
            $isPrivate = $this->getData($path . '/private');
            //skip base release, non-@api classes and private methods/properties
            $version = ($this->getBaseRelease() == $release || !$isAPI || $isPrivate) ? '' : $version;
            return $version;
        }
        return $this->getPrevious()->getCreatedSinceInformation($path);
    }

    /**
     * @return mixed
     */
    public function getBaseRelease()
    {
        return $this->baseReleases[$this->edition];
    }

    public function getData($key = '', $index = null)
    {
        if ('' === $key) {
            return $this->data;
        }

        /* process a/b/c key as ['a']['b']['c'] */
        if (strpos($key, '/')) {
            $data = $this->getDataByPath($key);
        } else {
            $data = $this->_getData($key);
        }

        if ($index !== null) {
            if ($data === (array)$data) {
                $data = isset($data[$index]) ? $data[$index] : null;
            } elseif (is_string($data)) {
                $data = explode(PHP_EOL, $data);
                $data = isset($data[$index]) ? $data[$index] : null;
            } elseif ($data instanceof DataStructure) {
                $data = $data->getData($index);
            } else {
                $data = null;
            }
        }
        return $data;
    }

    /**
     * Get object data by path
     *
     * Method consider the path as chain of keys: a/b/c => ['a']['b']['c']
     *
     * @param string $path
     * @return mixed
     */
    private function getDataByPath($path)
    {
        $keys = explode('/', $path);

        $data = $this->data;
        foreach ($keys as $key) {
            if ((array)$data === $data && isset($data[$key])) {
                $data = $data[$key];
            } elseif ($data instanceof DataStructure) {
                $data = $data->getDataByKey($key);
            } else {
                return null;
            }
        }
        return $data;
    }

    /**
     * Get object data by particular key
     *
     * @param string $key
     * @return mixed
     */
    private function getDataByKey($key)
    {
        return $this->_getData($key);
    }

    /**
     * Get value from _data array without parse key
     *
     * @param   string $key
     * @return  mixed
     */
    private function _getData($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
}
