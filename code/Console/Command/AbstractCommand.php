<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\DeprecationTool\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Magento\DeprecationTool\AppConfig;

abstract class AbstractCommand extends Command
{
    protected $requiredOptions = [
        'edition', 'tags', 'latest', 'branch'
    ];

    protected $validEditions = [
        AppConfig::CE_EDITION,
        AppConfig::EE_EDITION,
        AppConfig::B2B_EDITION
    ];

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('default')
            ->setDescription('common command')
            ->addOption(
                'edition',
                null,
                InputOption::VALUE_REQUIRED,
                'Edition to work with (e.g. ce)'
            )->addOption(
                'tags',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma separated list of previous releases to compare against.'
            )
            ->addOption(
                'latest',
                null,
                InputOption::VALUE_REQUIRED,
                'Tag for latest release.'
            )
            ->addOption(
                'branch',
                null,
                InputOption::VALUE_REQUIRED,
                'Release branch'
            );
    }

    /**
     * Get and validate user input from the command line
     *
     * @param InputInterface $input
     * @return array
     */
    protected function getUserInput(InputInterface $input)
    {
        $editionArg = $input->getOption('edition');
        $tagsArg = $input->getOption('tags');
        $latestArg = $input->getOption('latest');
        $branchArg = $input->getOption('branch');

        foreach ($this->requiredOptions as $option) {
            if (empty($input->getOption($option))) {
                throw new \Symfony\Component\Console\Exception\InvalidOptionException('Option required: ' . $option);
            }
        }

        if (!in_array($editionArg, $this->validEditions)) {
            throw new \Symfony\Component\Console\Exception\InvalidOptionException('Invalid edition: ' . $editionArg);
        }

        return [
            'edition' => $editionArg,
            'tags' => explode(',', $tagsArg),
            'latest' => $latestArg,
            'branch' => $branchArg
        ];
    }
}