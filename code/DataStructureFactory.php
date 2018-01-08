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
            if (!empty($versions)) {
                $latestVersion = array_shift($versions);
                $data = json_decode(file_get_contents($latestVersion['artifactPath']), true);
                $structure = new DataStructure($data, $latestVersion['version'], $packageName, $edition);
                $this->populateStructure($structure, $versions, $packageName, $edition);
                $output[$packageName] = $structure;
            }
        }

        return $output;
    }

    private function getVersions($packageName)
    {
        $files = [];
        foreach ($this->appConfig->getEditions() as $edition) {
            foreach ($this->appConfig->getTags($edition) as $release) {
                $packageMetadataPath = MetadataRegistry::getPackageMetadataPath($edition, $release, $packageName);
                $versionFiles = glob($packageMetadataPath . '/*.json');
                $files = array_merge($files, $versionFiles);
            }
        }
        $versions = [];
        foreach ($files as $versionFilePath) {
            $version = substr(basename($versionFilePath), 0, -5);
            $versions[$version] = [
                'artifactPath' => $versionFilePath,
                'version' => $version
            ];
        }
        uksort($versions, 'version_compare');
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
        $data = json_decode(file_get_contents($version['artifactPath']), true);
        $structure->setPrevious(new DataStructure($data, $version['version'], $packageName, $edition));
        if (next($versions)) {
            $this->populateStructure($structure->getPrevious(), $versions, $packageName, $edition);
        }
    }
}
