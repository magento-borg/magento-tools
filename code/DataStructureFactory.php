<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool;

class DataStructureFactory
{
    /**
     * @var AppConfig
     */
    private $appConfig;

    /**
     * Initialize dependencies.
     *
     * @param AppConfig $appConfig
     */
    public function __construct(AppConfig $appConfig)
    {
        $this->appConfig = $appConfig;
    }

    /**
     * @param $edition
     * @return DataStructure[]
     */
    public function create($edition)
    {
        $output = [];
        $path = $this->appConfig->getGitSourceCodeLocation($edition, $this->appConfig->getLatestRelease($edition));
        $packages = PackagesListReader::getGitPackages($path);

        foreach (array_keys($packages) as $packageName) {
            $versions = $this->getVersions($packageName);
            $latestVersion = array_shift($versions);
            $artifactPath = $this->appConfig->getMetadataPath($packageName, $latestVersion);
            $data = json_decode(file_get_contents($artifactPath), true);
            $structure = new DataStructure($data, $latestVersion, $packageName, $edition);
            $this->populateStructure($structure, $versions, $packageName, $edition);
            $output[$packageName] = $structure;
        }

        return $output;
    }

    private function getVersions($packageName)
    {
        $artifactDirectory = $this->appConfig->getMetadataPath($packageName, null, false);
        $files = glob($artifactDirectory . '/*.json');
        $files = array_map('basename', $files);
        $versions = array_map(function ($item) { return substr($item, 0, -5); }, $files);
        usort($versions, 'version_compare');
        $versions = array_reverse($versions);
        return $versions;
    }

    /**
     * @param DataStructure $structure
     * @param $versions
     * @param $packageName
     */
    private function populateStructure(DataStructure $structure, $versions, $packageName, $edition)
    {
        $version = current($versions);
        if (!$version) {
            return;
        }
        $artifactPath = $this->appConfig->getMetadataPath($packageName, $version);
        $data = json_decode(file_get_contents($artifactPath), true);
        $structure->setPrevious(new DataStructure($data, $version, $packageName, $edition));
        if (next($versions)) {
            $this->populateStructure($structure->getPrevious(), $versions, $packageName, $edition);
        }
    }
}
