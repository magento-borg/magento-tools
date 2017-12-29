<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

class MetadataRegistry
{
    /**
     * Check if package metadata exists for a given release
     *
     * @param string $edition
     * @param string $release
     * @param string $package
     * @return bool
     */
    public static function hasPackageMetadata($edition, $release, $package)
    {
        $packageMetadataPath = self::getPackageMetadataPath($edition, $release, $package);
        if (file_exists($packageMetadataPath) && is_dir($packageMetadataPath)) {
            return true;
        }
        return false;
    }

    /**
     * Check if metadata exists for a given release
     *
     * @param string $edition
     * @param string $release
     * @return bool
     */
    public static function hasReleaseMetadata($edition, $release)
    {
        $releaseMetadataPath = self::getReleaseMetadataPath($edition, $release);
        if (file_exists($releaseMetadataPath) && is_dir($releaseMetadataPath)) {
            return true;
        }
        return false;
    }

    /**
     * Get path to edition release metadata
     *
     * @param string $edition
     * @param string $release
     * @return string
     */
    public static function getReleaseMetadataPath($edition, $release)
    {
        $path = BP . '/var/metadata/' . $release . '/magento' . $edition;
        return $path;
    }

    /**
     * Get path to package metadata for release
     *
     * @param string $edition
     * @param string $release
     * @param string $package
     * @return string
     */
    public static function getPackageMetadataPath($edition, $release, $package)
    {
        $package = str_replace('/', '-', $package);
        $path = self::getReleaseMetadataPath($edition, $release) . '/' . $package;
        return $path;
    }

    /**
     * Get path to metadata for package version
     *
     * @param string $edition
     * @param string $release
     * @param string $package
     * @param string $version
     * @return string
     */
    public static function getPackageVersionMetadataPath($edition, $release, $package, $version)
    {
        $path = self::getPackageMetadataPath($edition, $release, $package) . '/' . $version . '.json';
        return $path;
    }
}
