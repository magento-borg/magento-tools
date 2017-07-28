<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

interface CheckoutStrategyInterface
{
    /**
     * @param string $release
     * @param string $commit
     * @return void
     */
    public function checkout($release, $commit);
}
