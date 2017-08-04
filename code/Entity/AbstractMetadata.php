<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\Entity;

abstract class AbstractMetadata
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $isDeprecated;

    /**
     * @var string
     */
    protected $deprecatedSince;

    /**
     * @var bool
     */
    protected $see;

    /**
     * @var string
     */
    protected $since;

    /**
     * @var string
     */
    protected $release;

    /**
     * @var string
     */
    protected $package;

    /**
     * @var string
     */
    protected $packageVersion;

    /**
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param string $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }

    /**
     * @return string
     */
    public function getPackageVersion()
    {
        return $this->packageVersion;
    }

    /**
     * @param string $packageVersion
     */
    public function setPackageVersion($packageVersion)
    {
        $this->packageVersion = $packageVersion;
    }

    /**
     * @return string
     */
    public function getRelease()
    {
        return $this->release;
    }

    /**
     * @param string $release
     */
    public function setRelease($release)
    {
        $this->release = $release;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isDeprecated()
    {
        return $this->isDeprecated;
    }

    /**
     * @param bool $isDeprecated
     */
    public function setIsDeprecated(bool $isDeprecated)
    {
        $this->isDeprecated = $isDeprecated;
    }

    /**
     * @return string
     */
    public function getDeprecatedSince()
    {
        return $this->deprecatedSince;
    }

    /**
     * @param string $deprecatedSince
     */
    public function setDeprecatedSince($deprecatedSince)
    {
        $this->deprecatedSince = $deprecatedSince;
    }

    /**
     * @return bool
     */
    public function hasSee()
    {
        return $this->see;
    }

    /**
     * @param bool $see
     */
    public function setHasSee(bool $see)
    {
        $this->see = $see;
    }

    /**
     * @return string
     */
    public function getSince()
    {
        return $this->since;
    }

    /**
     * @param string $since
     */
    public function setSince($since)
    {
        $this->since = $since;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'isDeprecated' => $this->isDeprecated,
            'deprecatedSince' => $this->deprecatedSince,
            'see' => $this->see,
            'since' => $this->since,
            'release' => $this->release,
            'package' => $this->package,
            'packageVersion' => $this->packageVersion,
        ];
    }
}
