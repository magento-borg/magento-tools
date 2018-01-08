<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool;

interface ProjectStrategyInterface
{
    /**
     * @param string $release
     * @param string|array $commit
     * @return void
     */
    public function checkout($release, $commit);

    /**
     * @param string $release
     * @param string $message
     * @return mixed
     */
    public function deploy($release, $message);
}
