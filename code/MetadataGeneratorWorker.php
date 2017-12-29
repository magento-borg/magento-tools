<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

use Composer\Autoload\ClassLoader;

class MetadataGeneratorWorker
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
        $logger = new \Zend_Log();
        $writer = new \Zend_Log_Writer_Stream($this->appConfig->getLogPath('metadata-generator.log'));
        $writer->setFormatter(new \Zend_Log_Formatter_Simple('%timestamp% ' . $this->data['name'] . ':' . $this->data['version'] . ' %priorityName%: %message%' . PHP_EOL));
        $logger->addWriter($writer);

        $logger->info('Processing');

        $this->classLoader->register();
        $this->processFiles($this->files, $this->autoloader, $this->data, $logger);
    }

    /**
     * @param $files
     * @param ClassLoader $classLoader
     * @param $config
     */
    private function processFiles($files, ClassLoader $classLoader, $config, \Zend_Log $logger)
    {
        $metadata = [];
        foreach ($files as $file) {
            foreach ($this->classReader->read($file, $classLoader, $config, $logger) as $meta) {
                $metadata[$meta->getName()] = $meta;
            }
        }
        $dataArray = array_map(
            function (Entity\ClassMetadata $item) {
                return $item->toArray();
            },
            $metadata
        );
        $artifactDirectory = MetadataRegistry::getPackageMetadataPath(
            $config['edition'],
            $config['release'],
            $config['name']
        );
        if (!file_exists($artifactDirectory)) {
            $this->appConfig->createFolder($artifactDirectory);
        }
        $artifactPath = MetadataRegistry::getPackageVersionMetadataPath(
            $config['edition'],
            $config['release'],
            $config['name'],
            $config['version']
        );

        file_put_contents($artifactPath, json_encode($dataArray, JSON_PRETTY_PRINT));
        unset($metadata);
        unset($dataArray);
    }
}
