<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\DeprecationTool;

class DataStructureFactory
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Initialize dependencies.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param $edition
     * @return DataStructure
     */
    public function create($edition)
    {
        $tags = $this->config->getTags($edition);
        $latestTag = $this->config->getLatestRelease($edition);

        $artifactPath = $this->config->getArtifactPath($edition, $latestTag);

        $data = json_decode(file_get_contents($artifactPath), true);
        $structure = new DataStructure($data, $latestTag); //latest tag
        $this->populateStructure($structure, $tags, $edition);
        return $structure;
    }

    /**
     * @param DataStructure $structure
     * @param array $tags
     * @param $edition
     * @return void
     */
    private function populateStructure(DataStructure $structure, $tags, $edition)
    {
        $tag = current($tags);
        if (!$tag) {
            return;
        }
        $artifactPath = $this->config->getArtifactPath($edition, $tag);
        $data = json_decode(file_get_contents($artifactPath), true);
        $structure->setPrevious(new DataStructure($data, $tag));
        if (next($tags)) {
            $this->populateStructure($structure->getPrevious(), $tags, $edition);
        }
    }
}
