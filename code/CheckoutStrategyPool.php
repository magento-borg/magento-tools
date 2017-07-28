<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

class CheckoutStrategyPool
{
    /**
     * @var CheckoutStrategyInterface[]
     */
    private $pool;

    /**
     * Initialize dependencies.
     *
     * @param CheckoutStrategyInterface[] $strategies
     */
    public function __construct(array $strategies)
    {
        $this->pool = $strategies;
    }

    /**
     * @param string $edition
     * @return CheckoutStrategyInterface
     */
    public function getStrategy($edition)
    {
        return $this->pool[$edition];
    }
}
