<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Execute a command with automatic escaping of arguments
 *
 * @param string $command
 * @return array
 * @throws Exception
 */
function execVerbose($command)
{
    $args = func_get_args();
    $args = array_map('escapeshellarg', $args);
    $args[0] = $command;
    $command = call_user_func_array('sprintf', $args);
    echo $command . PHP_EOL;
    exec($command, $output, $exitCode);
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }
    if (0 !== $exitCode) {
        throw new Exception("Command has failed with exit code: $exitCode.");
    }
    return $output;
}