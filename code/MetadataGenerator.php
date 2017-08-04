<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

use Composer\Autoload\ClassLoader;

class MetadataGenerator
{
    /**
     * @param AppConfig $appConfig
     * @param FileReader $fileReader
     * @param ClassReader $classReader
     * @param ClassLoader $loader
     */
    public function generate(AppConfig $appConfig, FileReader $fileReader, ClassReader $classReader, ClassLoader $loader)
    {
        $logger = new \Zend_Log();
        $infoWriter = new \Zend_Log_Writer_Stream($appConfig->getLogPath('metadata-generator.log'));
        $logger->addWriter($infoWriter);

        /** @var MetadataGeneratorThread[] $workers */
        $jobs = [];
        $workers = [];
        $directories = [];
        foreach ($appConfig->getEditions() as $index => $edition) {
            $path = $appConfig->getGitSourceCodeLocation($edition, $appConfig->getLatestRelease($edition));
            foreach (PackagesListReader::getGitPackages($path) as $name => $info) {
                if (!isset($directories[$name][$info['version']])) {
                    $directories[$name][$info['version']] = [
                        'path' => $info['path'],
                        'autoloader' => $appConfig->getSourceCodePath($edition, $appConfig->getLatestRelease($edition)) . '/vendor/autoload.php',
                        'version' => $info['version'],
                        'release' => $appConfig->getLatestRelease($edition),
                        'edition' => $edition,
                        'name' => $name
                    ];
                }
            }
            foreach ($appConfig->getTags($edition) as $tag) {
                $path = $appConfig->getSourceCodePath($edition, $tag);
                foreach (PackagesListReader::getComposerPackages($path) as $name => $info) {
                    if (!isset($directories[$name][$info['version']])) {
                        $directories[$name][$info['version']] = [
                            'path' => $info['path'],
                            'autoloader' => $appConfig->getSourceCodePath($edition, $tag) . '/vendor/autoload.php',
                            'version' => $info['version'],
                            'release' => $tag,
                            'edition' => $edition,
                            'name' => $name
                        ];
                    }
                }
            }
        }

        foreach ($directories as $packageName => $versions) {
            foreach ($versions as $version => $config) {
                $key = $config['name'] . ' = ' . $config['version'];
                $artifactPath = $appConfig->getMetadataPath($config['name'], $config['version']);
                if (file_exists($artifactPath)) {
                    //$logger->info('Skipped: ' . $config['name'] . ' = ' . $config['version']);
                    continue;
                }

                $paths = $config['path'];
                $autoloader = $config['autoloader'];
                $files = $fileReader->read($paths, '*.php');
                $jobs[$key] = new MetadataGeneratorThread($files, $autoloader, $appConfig, $config, $classReader, $loader);
            }
        }

        $threads = $appConfig->getThreadsCount();
        $logger->info('Running ' . $threads . ' threads');
        for ($i = 0; $i < $threads; $i++) {
            if (empty($jobs)) {
                break;
            }
            $name = key($jobs);
            $workers[$name] = array_shift($jobs);
            $workers[$name]->start();
        }

        while (!empty($workers)) {
            foreach ($workers as $index => $worker) {
                if (!$worker->isRunning()) {
                    unset($workers[$index]);
                    $logger->info('Completed ' . $index. '. Jobs left: ' . count($jobs));

                    if (!empty($jobs)) {
                        $name = key($jobs);
                        $workers[$name] = array_shift($jobs);
                        $workers[$name]->start();
                    }
                }
            }
            sleep(1);
        }
        $logger->info('All done');
    }
}
