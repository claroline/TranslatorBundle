<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle\Manager;

use Claroline\BundleRecorder\Log\LoggableTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Library\Utilities\FileSystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @DI\Service("claroline.translation.manager.git_manager")
 */
class GitManager
{
    use LoggableTrait;

    /**
     * @DI\InjectParams({
     *     "gitDirectory"       = @DI\Inject("%claroline.param.git_directory%"),
     *     "translationManager" = @DI\Inject("claroline.translation.manager.translation_manager"),
     *     "gitConfig"          = @DI\Inject("%claroline.param.git_config%"),
     *     "repositories"       = @DI\Inject("%claroline.param.git_repositories%")
     * })
     */
    public function __construct(
        $gitDirectory, 
        TranslationManager $translationManager,
        $gitConfig,
        $repositories
    )
    {
        $this->gitDirectory       = $gitDirectory;
        $this->translationManager = $translationManager;
        $this->gitConfig          = $gitConfig;
        $this->repositories       = $repositories;
    }

    public function add($language)
    {

    }

    public function pull($vendor, $bundle)
    {
        $this->log('Pulling ' . $vendor . ' ' . $bundle . '...');
    }

    public function push($vendor, $bundle)
    {
        $this->log('Pushing ' . $vendor . ' ' . $bundle . '...');
    }

    public function build($vendor, $bundle)
    {
        $this->log('Building ' . $vendor . ' ' . $bundle . '...');
    }

    public function init($vendor, $bundle)
    {
        if ($this->exists($vendor, $bundle)) {
            $this->log('Cannot initialize ' . $vendor . $bundle . ': directory already exists.', LogLevel::DEBUG);

            return false;
        } 

        $this->addRepository($vendor, $bundle);     
        $workingDir = $this->gitDirectory . $vendor . $bundle;
        $repo = 'https://github.com/' . $vendor . '/' . $bundle .'.git';
        $fs = new FileSystem();
        $this->log('Initialize ' . $vendor . ' ' . $bundle . '...');
        $this->log('Setting up git...');
        exec('git init ' . $workingDir);          
        $this->log('git init ' . $workingDir);
        $this->log('Change dir to ' . $workingDir);
        chdir($workingDir);
        $this->log('git remote add -f origin ' . $repo);
        exec('git remote add -f origin ' . $repo);
        $this->log('git config core.sparseCheckout true');
        exec('git config core.sparseCheckout true');
        $this->log('git config core.filemode false');
        exec('git config core.filemode false');
        $this->log('echo Resource/translations/* >> .git/info/sparse-checkout');
        exec('echo Resources/translations/* >> .git/info/sparse-checkout');
        $this->log('git pull --depth=1 origin master');
        exec('git pull --depth=1 origin master');
        $this->log('Git was set up for ' . $vendor . $bundle . '.');
        $this->translationManager->init($vendor, $bundle);
    }

    public function remove($vendor, $bundle) 
    {
        $fs = new FileSystem();
        $workingDir = $this->gitDirectory . $vendor . $bundle;
        $this->log('Removing ' . $workingDir . '...', LogLevel::DEBUG);
        $fs->rmdir($workingDir, true);
        $this->translationManager->clear($vendor, $bundle);
        $this->removeRepository($vendor, $bundle);
    }

    public function exists($vendor, $bundle)
    {
        if (is_dir($this->gitDirectory . $vendor . $bundle)) {
            return true;
        }

        return false;
    }

    public function isRunnable()
    {
        $execEnabled = function_exists('exec') &&
            !in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions')))) &&
            strtolower(ini_get('safe_mode')) != 1;

        //no check yet
        $gitExists = true;

        return $execEnabled && $gitExists;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->translationManager->setLogger($logger);
    }

    public function addRepository($vendor, $bundle)
    {
        $repositories = file_exists($this->repositories) ? Yaml::parse($this->repositories): array();
        if ($repositories === true) $configs = array();
        $repositories[$vendor . $bundle] = array($vendor => $bundle);

        if (!file_put_contents($this->repositories, Yaml::dump($repositories, 2))) {
            $this->log("Couldn't add git config in " . $this->repositories . " !!!", LogLevel::DEBUG);
        }
    }

    public function removeRepository($vendor, $bundle)
    {
        $this->log('Removing repo not implemented yet');
    }

    public function getRepositories()
    {
        return file_exists($this->repositories) ? Yaml::parse($this->repositories): array();
    }
}