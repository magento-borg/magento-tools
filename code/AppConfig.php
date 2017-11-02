<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

class AppConfig
{
    const CE_EDITION = 'ce';
    const EE_EDITION = 'ee';
    const B2B_EDITION = 'b2b';

    const TYPE_DEPRECATED_CLASSES = 'deprecated-classes';
    const TYPE_DEPRECATED_METHODS = 'deprecated-methods';
    const TYPE_DEPRECATED_PROPERTIES = 'deprecated-properties';

    const TYPE_SINCE_CLASSES = 'since-classes';
    const TYPE_SINCE_METHODS = 'since-methods';
    const TYPE_SINCE_PROPERTIES = 'since-properties';

    /**
     * @var array
     */
    private $config;

    /**
     * Initialize dependencies.
     */
    public function __construct()
    {
        $file = file_exists(BP . '/config.ini') ? BP . '/config.ini' : BP . '/config.ini.dist';
        $this->config = parse_ini_file($file, true);
    }

    /**
     * @param string $edition
     * @return string[]
     */
    public function getTags($edition)
    {
        $tags = isset($this->config[$edition. '_tags']['release']) ? $this->config[$edition. '_tags']['release'] : [];
        usort($tags, 'version_compare');
        return $tags;
    }

    /**
     * @param string $edition
     * @return string
     */
    public function getRepository($edition)
    {
        return $this->config[$edition . '_repository'];
    }

    /**
     * @param string $edition
     * @return string
     */
    public function getLatestRelease($edition)
    {
        return $this->config[$edition . '_latest']['release'];
    }

    /**
     * @param string $edition
     * @return string
     */
    public function getLatestCommit($edition)
    {
        return $this->config[$edition . '_latest']['commit'];
    }

    /**
     * @return string[]
     */
    public function getEditions()
    {
        return $this->config['editions']['edition'];
    }

    /**
     * @param $edition
     * @param $release
     * @return string
     */
    public function getSourceCodePath($edition, $release)
    {
        $path = BP . '/../var/releases/' . $release . '/magento2' . $edition;
        return $path;
    }

    /**
     * @param $type
     * @param null $packageName
     * @return string
     */
    public function getChangelogPath($type, $packageName = null)
    {
        if ($packageName) {
            $packageName = str_replace('/', '-', $packageName);
            return BP . '/../var/changelog/' . $type . '/' . $packageName . '.json';
        }
        $path = BP . '/../var/changelog/' . $type;
        return $path;
    }

    /**
     * @param $type
     * @return string
     */
    public function getLogPath($type)
    {
        $path = BP . '/../var/log/' . $type;
        return $path;
    }

    /**
     * @param $edition
     * @param $release
     * @return string
     */
    public function getGitSourceCodeLocation($edition, $release)
    {
        if ($edition == 'ce') {
            return BP . '/../var/releases/' . $release . '/magento2' . $edition;
        } else {
            return BP . '/../var/releases/' . $release . '/magento2' . $edition . '/magento2' . $edition;
        }

    }

    /**
     * @param string $edition
     * @return string string
     */
    public function getMasterSourceCodePath($edition)
    {
        $path = BP . '/../var/master/magento2' . $edition;
        return $path;
    }

    public function createFolder($path)
    {
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * @param $edition
     * @param $release
     * @param bool $withFileName
     * @return string
     */
    public function getArtifactPath($edition, $release, $withFileName = true)
    {
        if ($withFileName) {
            $path = BP . '/../var/metadata/' . $release . '/magento2' . $edition . '.json';
        } else {
            $path = BP . '/../var/metadata/' . $release;
        }
        return $path;
    }

    /**
     * @param $package
     * @param $version
     * @param bool $withFileName
     * @return string
     */
    public function getMetadataPath($package, $version, $withFileName = true)
    {
        $package = str_replace('/', '-', $package);
        if ($withFileName) {
            $path = BP . '/../var/metadata/' . $package . '/' . $version . '.json';
        } else {
            $path = BP . '/../var/metadata/' . $package;
        }
        return $path;
    }

    /**
     * @return int
     */
    public function getThreadsCount()
    {
        return isset($this->config['threads']) ? intval($this->config['threads']) : 10;
    }
}
