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
     * @var AppConfig
     */
    private $appConfig;
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
     * @param AppConfig $config
     * @param $data
     * @param ClassReader $classReader
     * @param ClassLoader $loader
     */
    public function __construct($files, $autoloader, AppConfig $config, $data, ClassReader $classReader, ClassLoader $loader)
    {
        $this->files = $files;
        $this->autoloader = $autoloader;
        $this->appConfig = $config;
        $this->classReader = $classReader;
        $this->classLoader = $loader;
        $this->data = $data;
    }


    public function run()
    {
        $this->classLoader->register();
        $this->processFiles($this->files, $this->autoloader, $this->data);
    }

    /**
     * @param $files
     * @param $autoloader
     * @param $config
     */
    private function processFiles($files, $autoloader, $config)
    {
        $metadata = [];
        foreach ($files as $file) {
            foreach ($this->classReader->read($file, $autoloader, $config) as $meta) {
                $metadata[$meta->getName()] = $meta;
            }
        }
        $dataArray = array_map(
            function (Entity\ClassMetadata $item) {
                return $item->toArray();
            },
            $metadata
        );
        $artifactDirectory = $this->appConfig->getMetadataPath($config['name'], $config['version'], false);
        if (!file_exists($artifactDirectory)) {
            $this->appConfig->createFolder($artifactDirectory);
        }
        $artifactPath = $this->appConfig->getMetadataPath($config['name'], $config['version']);
        file_put_contents($artifactPath, json_encode($dataArray, JSON_PRETTY_PRINT));
        unset($metadata);
        unset($dataArray);
    }
}
