<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Claroline\CoreBundle\Library\Logger\ConsoleLogger;
use Psr\Log\LogLevel;

class TranslatorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('claroline:git:translations')
            ->setDescription('Manages the translations from git');
        $this->setDefinition(
            array(
                new InputArgument('vendor', InputArgument::OPTIONAL, 'The vendor name'),
                new InputArgument('bundle', InputArgument::OPTIONAL, 'The bundle name')
            )
        );
        $this->addOption(
            '--pull',
            'pull',
            InputOption::VALUE_NONE,
            'Pulls the last changes from git.'
        );
        $this->addOption(
            '--push',
            'push',
            InputOption::VALUE_NONE,
            'Push the last changes from the database.'
        );
        $this->addOption(
            '--build',
            'build',
            InputOption::VALUE_NONE,
            'Build the new translation file.'
        );
        $this->addOption(
            '--init',
            'init',
            InputOption::VALUE_NONE,
            'Initilize the working directory.'
        );
        $this->addOption(
            '--remove',
            'remove',
            InputOption::VALUE_NONE,
            'Removes the working directory.'
        );
        $this->addOption(
            '--all',
            'all',
            InputOption::VALUE_NONE,
            'Build the new translation file.'
        );
    }

    protected function askArgument(OutputInterface $output, $argumentName)
    {
        $argument = $this->getHelper('dialog')->askAndValidate(
            $output,
            "Enter bundle {$argumentName}: ",
            function ($argument) {
                if (empty($argument)) {
                    throw new \Exception('This argument is required');
                }

                return $argument;
            }
        );

        return $argument;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consoleLogger = ConsoleLogger::get($output);
        $gitManager = $this->getContainer()->get('claroline.translation.manager.git_manager');
        $gitManager->setLogger($consoleLogger);

        if (!$gitManager->isRunnable()) {
            $consoleLogger->log('The exec() function is not enabled. Please edit your php.ini.', LogLevel::DEBUG);

            return false;
        }

        $vendor = $input->getArgument('vendor');
        $bundle = $input->getArgument('bundle');
        $all    = $input->getOption('all');

        if ((!$vendor || !$bundle) && !$all) {
            throw new \Exception('The vendor and bundle are required.');
        }

        if ($all) {
            $fqcns = $gitManager->getRepositories();
        } else {
            $fqcns[] = array('vendor' => $vendor, 'bundle' => $bundle);
        }

        foreach ($fqcns as $fqcn) {
            if ($input->getOption('remove'))  $gitManager->remove($fqcn['vendor'], $fqcn['bundle']);
            if ($input->getOption('init'))    $gitManager->init($fqcn['vendor'], $fqcn['bundle']);
            if ($input->getOption('pull'))    $gitManager->pull($fqcn['vendor'], $fqcn['bundle']);
            if ($input->getOption('build'))   $gitManager->build($fqcn['vendor'], $fqcn['bundle']);
            if ($input->getOption('push'))    $gitManager->push($fqcn['vendor'], $fqcn['bundle']);
        }
    }
}
