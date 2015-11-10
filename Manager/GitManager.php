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
     *     "repositories"       = @DI\Inject("%claroline.param.git_repositories%"),
     *     "om"                 = @DI\Inject("claroline.persistence.object_manager"),
     *     "devTranslator"      = @DI\Inject("claroline.dev_manager.translation_manager")
     * })
     */
    public function __construct(
        $gitDirectory, 
        TranslationManager $translationManager,
        $gitConfig,
        $repositories,
        $om,
        $devTranslator
    )
    {
        $this->gitDirectory       = $gitDirectory;
        $this->translationManager = $translationManager;
        $this->gitConfig          = $gitConfig;
        $this->repositories       = $repositories;
        $this->om                 = $om;
        $this->repo               = $om->getRepository('ClarolineTranslatorBundle:TranslationItem');
        $this->devTranslator      = $devTranslator;
    }

    public function pull($vendor, $bundle)
    {
        $this->log('Pulling ' . $vendor . ' ' . $bundle . '...');
    }

    public function build($vendor, $bundle)
    {
        $this->log('Building ' . $vendor . ' ' . $bundle . '...');
    }

    public function commit($vendor, $bundle) {
        //build new files from the database;
        $items = $this->repo->findBy(array(
                'vendor' => $vendor,
                'bundle' => $bundle,
                'commit' => $this->translationManager->getCurrentCommit($vendor . $bundle)
            )
        );

        $this->log('Commiting ' . count($items) . ' translations...');
        $data = $this->serializeForCommit($items);
        $this->buildFilesToCommit($data, $vendor, $bundle);
        $this->log('Please commit and push manually.');
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

        //set the translations for each supported languages
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

    private function buildFilesToCommit($domains, $vendor, $bundle)
    {
        foreach ($domains as $domainName => $domain) {
            foreach ($domain as $lang => $translations) {
                $fileName = $domainName . '.' . $lang . '.yml';
                $els = array();

                foreach ($translations as $key => $value) {
                    preg_match_all('/\[([^]]+)\]/', $key, $matches, PREG_SET_ORDER);
                    $els[] = $this->recursiveParseKeys($matches, $value); 
                }

                $datadump = $this->recursiveMergeTranslations($els);
                $yaml = Yaml::dump($datadump, 2);
                file_put_contents(
                    $this->translationManager->getTranslationsDirectory($vendor . $bundle) . '/' . $fileName,
                    $yaml
                );
            }
        }
    }

    private function serializeForCommit(array $items) 
    {
        $data = [];

        foreach ($items as $item) {
            $data[$item->getDomain()][$item->getLang()][$item->getKey()] = $item->getTranslation(); 
        }

        return $data;
    }

    /* 
     * Returns a translation element like this: array(key1 => array(key2 => ... value))
     * because translations can be stored as array in different namespaces an makes evertything
     * much more complicated for me. This way we can sort of get the "namespace"
     * of the translation.
     * I'm also a wizzard.
     */
    private function recursiveParseKeys($keys, $value, $el = array(), $depth = 0)
    {
        $el[$keys[$depth][1]] = (++$depth < count($keys)) ? 
            $this->recursiveParseKeys(
                $keys, 
                $value, 
                $el[$keys[$depth][1]], 
                $depth
            ):
            $value;

        return $el;
    }

    /*
     * Now we need to merge all these elements. *sigh*.
     * @todo = make the buildChildTranslations recursive.

     */
    private function recursiveMergeTranslations(array $els)
    {
        $translations = array();

        //set the array_keys
        foreach ($els as $int => $el) {
            foreach ($el as $key => $value) {
                $translations[$key] = $value;
            }
        }

        //now we must build recursively child array and inject them;
        foreach ($els as $int => $el) {
            foreach ($el as $key => $value) {
                if (is_array($value)) {
                    $translations[$key] = $this->buildChildTranslationsForKey($els, $key);
                }
            }
        }

        return $translations;
    }

    /*/
     * This should be recursive because $value as $id => $translation may be found later in the tree
     */
    private function buildChildTranslationsForKey(array $els, $search)
    {
        $data = array();

        foreach ($els as $int => $el) {
            foreach ($el as $key => $value) {
                if ($search === $key) {
                    foreach ($value as $id => $translation) {
                        $data[$id] = $translation;
                    }
                }
            }
        }

        return $data;
    }
}