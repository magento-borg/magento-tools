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

        $jobs = [];
        $packages = [];

        foreach ($appConfig->getEditions() as $edition) {
            $gitPath = $appConfig->getGitSourceCodeLocation($edition, $appConfig->getLatestRelease($edition));
            $packagesGit = PackagesListReader::getGitPackages($gitPath);
            $release = $appConfig->getLatestRelease($edition);
            foreach ($packagesGit as $name => $info) {
                $packageVersion = $release . '-' . $info['version'];
                if (!isset($packages[$name][$packageVersion])) {
                    $packages[$name][$packageVersion] = [
                        'path' => $info['path'],
                        'autoloader' => $appConfig->getSourceCodePath($edition, $release) . '/vendor/autoload.php',
                        'version' => $info['version'],
                        'release' => $release,
                        'edition' => $edition,
                        'name' => $name
                    ];
                }
            }
            foreach ($appConfig->getTags($edition) as $tag) {
                $composerPath = $appConfig->getSourceCodePath($edition, $tag);
                $allPackagesComposer = PackagesListReader::getComposerPackages($composerPath);
                $editionPackagesComposer = array_intersect_key($allPackagesComposer, $packagesGit);
                foreach ($editionPackagesComposer as $name => $info) {
                    $packageVersion = $tag . '-' . $info['version'];
                    if (!isset($packages[$name][$packageVersion])) {
                        $packages[$name][$packageVersion] = [
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


        $autoloaders = [];
        foreach ($packages as $name => $versions) {
            foreach($versions as $config) {
                $key = $config['name'] . $config['release'] . $config['version'];
                if (MetadataRegistry::hasPackageMetadata($config['edition'], $config['release'], $config['name'])) {
                    //Don't generate metadata if it exists
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

                $jobs[$key] = new MetadataGeneratorWorker($files, $autoloader, $appConfig, $config, $classReader, $loader);
                $jobs[$key]->run();
            }
        }
    }
}
