<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

class Config
{
    const CE_EDITION = 'ce';
    const EE_EDITION = 'ee';
    const B2B_EDITION = 'b2b';

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
        return isset($this->config[$edition. '_tags']['release']) ? $this->config[$edition. '_tags']['release'] : [];
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
        $path = BP . '/var/releases/' . $release . '/magento2' . $edition;
        return $path;
    }

    /**
     * @param $edition
     * @return string
     */
    public function getChangelogPath($edition)
    {
        $path = BP . '/var/changelog/' . $edition;
        return $path;
    }

    /**
     * @param $type
     * @return string
     */
    public function getLogPath($type)
    {
        $path = BP . '/var/log/' . $type;
        return $path;
    }

    /**
     * @param $edition
     * @param $release
     * @return string
     */
    public function getSourceCodeLocation($edition, $release)
    {
        if ($edition == self::CE_EDITION) {
            $path = BP . '/var/releases/' . $release . '/magento2' . $edition;
        } else {
            // Ex: /var/release/2.2.0/magento2ee/magento2ee; magento2ee is a sub-folder of root directory.
            $path = BP . '/var/releases/' . $release . '/magento2' . $edition . '/magento2' . $edition;
        }

        return $path;
    }

    /**
     * @param string $edition
     * @return string string
     */
    public function getMasterSourceCodePath($edition)
    {
        $path = BP . '/var/master/magento2' . $edition;
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
            $path = BP . '/var/metadata/' . $release . '/magento2' . $edition . '.json';
        } else {
            $path = BP . '/var/metadata/' . $release;
        }
        return $path;
    }
}
