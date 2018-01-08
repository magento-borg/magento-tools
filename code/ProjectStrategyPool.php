<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

class ProjectStrategyPool
{
    /**
     * @var ProjectStrategyInterface[]
     */
    private $pool;

    /**
     * Initialize dependencies.
     *
     * @param ProjectStrategyInterface[] $strategies
     */
    public function __construct(array $strategies)
    {
        $this->pool = $strategies;
    }

    /**
     * @param string $edition
     * @return ProjectStrategyInterface
     */
    public function getStrategy($edition)
    {
        return $this->pool[$edition];
    }
}
