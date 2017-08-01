<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

use Composer\Autoload\ClassLoader;

class MetadataGeneratorThread extends \Thread
{
    private $files;
    private $autoloader;
    /**
     * @var Config
     */
    private $config;
    private $edition;
    private $artifactPath;
    /**
     * @var ClassReader
     */
    private $classReader;

    /**
     * @var ClassLoader
     */
    private $classLoader;

    /**
     * @var array
     */
    private $data;

    /**
     * Initialize dependencies.
     *
     * @param $files
     * @param $autoloader
     * @param Config $config
     * @param $edition
     * @param $artifactPath
     * @param ClassReader $classReader
     */
    public function __construct($files, $autoloader, Config $config, $data, $edition, $artifactPath, ClassReader $classReader, ClassLoader $loader)
    {
        $this->files = $files;
        $this->autoloader = $autoloader;
        $this->config = $config;
        $this->edition = $edition;
        $this->artifactPath = $artifactPath;
        $this->classReader = $classReader;
        $this->classLoader = $loader;
        $this->data = $data;
    }


    public function run()
    {
        $this->classLoader->register();
        $this->processFiles($this->files, $this->autoloader, $this->data, $this->edition, $this->artifactPath);
    }

    /**
     * @param $files
     * @param $autoloader
     * @param $config
     * @param $edition
     * @param $artifactPath
     */
    private function processFiles($files, $autoloader, $config, $edition, $artifactPath)
    {
        $metadata = [];
        foreach ($files as $file) {
            foreach ($this->classReader->read($file, $autoloader, $config['path']) as $meta) {
                $metadata[$meta->getName()] = $meta;
            }
        }
        $dataArray = array_map(
            function (Entity\ClassMetadata $item) {
                return $item->toArray();
            },
            $metadata
        );
        $artifactDirectory = $this->config->getArtifactPath($edition, $config['release'], false);
        if (!file_exists($artifactPath)) {
            $this->config->createFolder($artifactDirectory);
        }
        file_put_contents($artifactPath, json_encode($dataArray, JSON_PRETTY_PRINT));
        unset($metadata);
        unset($dataArray);
    }
}
