<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool\CodeUpdater;

use BetterReflection\Reflection\ReflectionClass;
use Composer\Autoload\ClassLoader;
use Magento\DeprecationTool\Config;

abstract class AbstractUpdater extends \Thread
{
    /**
     * @var ClassLoader
     */
    protected $classLoader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $edition;

    /**
     * Initialize dependencies.
     *
     * @param ClassLoader $classLoader
     * @param Config $config
     * @param string $edition
     */
    public function __construct(ClassLoader $classLoader, Config $config, $edition)
    {
        $this->classLoader = $classLoader;
        $this->config = $config;
        $this->edition = $edition;
    }

    public final function run()
    {
        if (!file_exists($this->config->getLogPath($this->edition))) {
            $this->config->createFolder($this->config->getLogPath($this->edition));
        }

        $this->classLoader->register();

        $logger = new \Zend\Log\Logger();
        $infoWriter = new \Zend\Log\Writer\Stream('php://output');
        $infoWriter->setFormatter('simple', ['format' => '%timestamp% ' . $this->getLogType() . ' %priorityName%: %message%', 'dateTimeFormat' => 'H:i:s']);

        $errorWriter = new \Zend\Log\Writer\Stream($this->config->getLogPath($this->edition . '/' . $this->getLogType() . '.error.log'));
        $errorWriter->addFilter(\Zend\Log\Logger::ERR);
        $errorWriter->setFormatter('simple', ['format' => '%timestamp% %priorityName%: %message%', 'dateTimeFormat' => 'H:i:s']);

        $logger->addWriter($infoWriter, \Zend\Log\Logger::INFO);
        $logger->addWriter($errorWriter, \Zend\Log\Logger::ERR);
        $this->execute($logger);

        $this->classLoader->unregister();
    }

    protected abstract function execute(\Zend\Log\Logger $logger);

    protected abstract function getLogType();

    protected function getChangeLog()
    {
        $changelogPath = $this->config->getChangelogPath($this->edition) . '/' . $this->getLogType() .'.json';
        if (!file_exists($changelogPath)) {
            return [];
        }
        $changelog = json_decode(file_get_contents($changelogPath), true);
        return $changelog;
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
        $autoloaderPath = $this->config->getSourceCodePath(
                $this->edition,
                $this->config->getLatestRelease($this->edition)
            ) . '/vendor/autoload.php';

        $classLoader = require $autoloaderPath;
        $reflector = new \BetterReflection\Reflector\ClassReflector(
            new \BetterReflection\SourceLocator\Type\ComposerSourceLocator($classLoader)
        );

        return $reflector;
    }
}
