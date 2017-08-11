<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflection\ReflectionClass;
use Composer\Autoload\ClassLoader;
use Magento\DeprecationTool\AppConfig;
use Magento\DeprecationTool\PackagesListReader;

abstract class AbstractUpdater extends \Thread
{
    /**
     * @var ClassLoader
     */
    protected $classLoader;

    /**
     * @var AppConfig
     */
    protected $appConfig;

    /**
     * @var string
     */
    protected $edition;

    /**
     * Initialize dependencies.
     *
     * @param ClassLoader $classLoader
     * @param AppConfig $config
     * @param string $edition
     */
    public function __construct(ClassLoader $classLoader, AppConfig $config, $edition)
    {
        $this->classLoader = $classLoader;
        $this->appConfig = $config;
        $this->edition = $edition;
    }

    public final function run()
    {
        $this->classLoader->register();

        $logger = new \Zend_Log();
        $infoWriter = new \Zend_Log_Writer_Stream('php://output');
        $infoWriter->setFormatter(new \Zend_Log_Formatter_Simple('%timestamp% ' . $this->getLogType() . ' %priorityName%: %message%'));

        $errorWriter = new \Zend_Log_Writer_Stream($this->appConfig->getLogPath('error.log'));
        $errorWriter->addFilter(\Zend_Log::ERR);
        $errorWriter->setFormatter(new \Zend_Log_Formatter_Simple('%timestamp% %priorityName%: %message%'));

        $logger->addWriter($infoWriter);
        $logger->addWriter($errorWriter);
        $this->execute($logger);

        $this->classLoader->unregister();
    }

    protected abstract function execute(\Zend_Log $logger);

    protected abstract function getLogType();

    protected function getChangeLog()
    {
        $output = [];
        $packages = PackagesListReader::getGitPackages($this->appConfig->getGitSourceCodeLocation($this->edition, $this->appConfig->getLatestRelease($this->edition)));
        foreach (array_keys($packages) as $packageName) {
            $changelogPath = $this->appConfig->getChangelogPath($this->getLogType(), $packageName);
            if (!file_exists($changelogPath)) {
                continue;
            }
            $changelog = json_decode(file_get_contents($changelogPath), true);
            $output = array_merge($output, $changelog);
        }
        return $output;
    }

    protected function saveContent(ReflectionClass $reflectionClass, $newContent)
    {
        $filePath = realpath($reflectionClass->getLocatedSource()->getFileName());
        file_put_contents($filePath, $newContent);
    }

    /**
     * @return \BetterReflection\Reflector\ClassReflector
     */
    protected function getClassReflector()
    {
        $autoloaderPath = $this->appConfig->getSourceCodePath(
                $this->edition,
                $this->appConfig->getLatestRelease($this->edition)
            ) . '/vendor/autoload.php';

        $classLoader = require $autoloaderPath;
        $reflector = new \BetterReflection\Reflector\ClassReflector(
            new \BetterReflection\SourceLocator\Type\ComposerSourceLocator($classLoader)
        );

        return $reflector;
    }
}
