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

        // TODO: debug
//        $directories = ['magento/framework' => $directories['magento/framework']];

        $autoloaders = [];

        foreach ($directories as $packageName => $versions) {
            foreach ($versions as $version => $config) {
                $key = $config['name'] . ' = ' . $config['version'];
                $artifactPath = $appConfig->getMetadataPath($config['name'], $config['version']);
                if (file_exists($artifactPath)) {
                    //$logger->info('Skipped: ' . $config['name'] . ' = ' . $config['version']);
                    continue;
                }

                $paths = $config['path'];
                if (!isset($autoloaders[$config['autoloader']])) {
                    foreach ($autoloaders as $autoloader) {
                        $autoloader->unregister();
                        \Closure::bind(function () {
                            foreach (self::$paths as $key => $path) {
                                self::$paths[$key] = [];
                            }
                        }, null, \Magento\Framework\Component\ComponentRegistrar::class)
                            ->__invoke();
                    }
                    $autoloaders[$config['autoloader']] = require_once $config['autoloader'];
                    $autoloaders[$config['autoloader']]->unregister();
                }
                $autoloader = $autoloaders[$config['autoloader']];
                $files = $fileReader->read($paths, '*.php');

                // TODO: debug
//                $index = 0;
//                foreach ($files as $key => $file) {
//                    if (strpos($file, 'Magento/Framework/Stdlib/DateTime.php')) {
//                        $index = $key;
//                        continue;
//                    }
//                }
//                $files = [$files[$index]];

                $jobs[$key] = new MetadataGeneratorThread($files, $autoloader, $appConfig, $config, $classReader, $loader);
                $jobs[$key]->run();
            }
        }

//        $threads = $appConfig->getThreadsCount();
//        $logger->info('Running ' . $threads . ' threads');
//        for ($i = 0; $i < $threads; $i++) {
//            if (empty($jobs)) {
//                break;
//            }
//            $name = key($jobs);
//            $workers[$name] = array_shift($jobs);
//            $workers[$name]->start();
//        }
//
//        while (!empty($workers)) {
//            foreach ($workers as $index => $worker) {
//                if (!$worker->isRunning()) {
//                    unset($workers[$index]);
//                    $logger->info('Completed ' . $index. '. Jobs left: ' . count($jobs));
//
//                    if (!empty($jobs)) {
//                        $name = key($jobs);
//                        $workers[$name] = array_shift($jobs);
//                        $workers[$name]->start();
//                    }
//                }
//            }
//            sleep(1);
//        }
//        $logger->info('All done');
    }
}
