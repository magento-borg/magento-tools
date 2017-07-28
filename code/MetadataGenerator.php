<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

use Composer\Autoload\ClassLoader;

class MetadataGenerator extends \Thread
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var FileReader
     */
    private $fileReader;

    /**
     * @var ClassReader
     */
    private $classReader;

    /**
     * @var $edition
     */
    private $edition;

    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * Initialize dependencies.
     *
     * @param Config $config
     * @param FileReader $fileReader
     * @param ClassReader $classReader
     * @param $edition
     */
    public function __construct(Config $config, FileReader $fileReader, ClassReader $classReader, $edition, ClassLoader $loader)
    {
        $this->config = $config;
        $this->fileReader = $fileReader;
        $this->classReader = $classReader;
        $this->edition = $edition;
        $this->loader = $loader;
    }

    public function run()
    {
        $this->loader->register();
        $edition = $this->edition;
        $directories = [];

        $tags = $this->config->getTags($edition);
        $tags[] = $this->config->getLatestRelease($edition);
        foreach ($tags as $tag) {
            $path = $this->config->getSourceCodeLocation($edition, $tag);
            $directories[$tag] = [
                'path' => $path,
                'autoloader' => $this->config->getSourceCodePath($edition, $tag) . '/vendor/autoload.php',
                'release' => $tag
            ];
        }

        foreach ($directories as $config) {
            $artifactPath = $this->config->getArtifactPath($edition, $config['release']);
            if (file_exists($artifactPath)) {
                continue;
            }
            $metadata = [];
            $paths = [];
            $paths[] = $config['path'] . '/app/code/Magento';
            $paths[] = $config['path'] . '/lib/internal/Magento';
            $paths[] = $config['path'] . '/setup/src/Magento/Setup/';
            $files = $this->fileReader->read($paths, '*.php');
            $autoloader = $config['autoloader'];
            foreach ($files as $file) {
                foreach ($this->classReader->read($file, $autoloader, $config['path']) as $meta) {
                    $metadata[$meta->getName()] = $meta;
                }
            }
            $dataArray = array_map(
                function (Entity\ClassMetadata $item) { return $item->toArray(); },
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
}
