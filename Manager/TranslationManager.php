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
use Claroline\TranslatorBundle\Entity\TranslationItem;
use Claroline\TranslatorBundle\Entity\Translation;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Yaml\Yaml;

/**
 * @DI\Service("claroline.translation.manager.translation_manager")
 */
class TranslationManager
{
    use LoggableTrait;

    const BATCH_SIZE = 250;

    /**
     * @DI\InjectParams({
     *     "gitDirectory"  = @DI\Inject("%claroline.param.git_directory%"),
     *	   "om"            = @DI\Inject("claroline.persistence.object_manager"),
     *	   "gitConfig"     = @DI\Inject("%claroline.param.git_config%"),
     *     "tokenStorage"  = @DI\Inject("security.token_storage"),
     *     "devTranslator" = @DI\Inject("claroline.dev_manager.translation_manager"),
     *     "authorization" = @DI\Inject("security.authorization_checker")
     * })
     */
    public function __construct(
        $gitDirectory,
        $om,
        $gitConfig,
        $tokenStorage,
        $devTranslator,
        $authorization
    )
    {
        $this->gitDirectory  = $gitDirectory;
        $this->om            = $om;
        $this->gitConfig     = $gitConfig;
        $this->repository    = $om->getRepository('ClarolineTranslatorBundle:TranslationItem');
        $this->tokenStorage  = $tokenStorage;
        $this->devTranslator = $devTranslator;
        $this->authorization = $authorization;
    }

    public function clear($vendor, $bundle)
    {
    	$this->log('Clearing the database for ' . $vendor . $bundle, LogLevel::DEBUG);
    	$translationItems = $this->repository
    		->findBy(array('vendor' => $vendor, 'bundle' => $bundle));

        $this->log('Removing ' . count($translationItems) . ' translations for ' . $vendor . ' ' . $bundle . '...');

    	foreach ($translationItems as $item) {
    		$this->om->remove($item);
    	}

    	$this->om->flush();
    }

    public function init($vendor, $bundle)
    {
    	$this->log('Setting up git config for ' . $vendor . $bundle . ' in ' . $this->gitConfig);

        $iterator = new \DirectoryIterator($this->getTranslationsDirectory($vendor . $bundle));
        $commit = $this->getCurrentCommit($vendor . $bundle);
        $configs = file_exists($this->gitConfig) ? Yaml::parse($this->gitConfig): array();
        if ($configs === true) $configs = array();
        $configs[$vendor . $bundle] = $commit;

        if (!file_put_contents($this->gitConfig, Yaml::dump($configs, 2))) {
        	$this->log("Couldn't add git config in " . $this->gitConfig . " !!!", LogLevel::DEBUG);
        }

        $this->setDatabaseFromYaml($vendor, $bundle);
    }

    public function pull($vendor, $bundle)
    {
        $this->setDatabaseFromYaml($vendor, $bundle);
    }

    /*
     *
     */
    private function recursiveParseTranslation(
        array $translations,
        $domain,
        $lang, 
        $commit,
        $vendor,
        $bundle,
        $path = '', 
        &$_i
    )
    {
        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                $this->recursiveParseTranslation(
                    $value, 
                    $domain, 
                    $lang, 
                    $commit, 
                    $vendor, 
                    $bundle,
                    $path . '[' . $key . ']', 
                    $_i
                );
            } else {
                $_i++;
                $item = new TranslationItem();
                $item->setKey($path . '[' . $key . ']');
                $item->setDomain($domain);
                $item->setLang($lang);
                $item->setCommit($commit);
                $item->setVendor($vendor);
                $item->setBundle($bundle);
                $this->om->persist($item);
                $this->addTranslation($item, $value);   
            }   

            if ($_i % self::BATCH_SIZE === 0) {
                $this->log('[UOW]: ' . $this->om->getUnitOfWork()->size());
                $this->log('Flushing ' . $_i . ' items...');
                $this->om->forceFlush();
                $this->om->clear();
                $this->log('[UOW]: ' . $this->om->getUnitOfWork()->size());
            }
        }
    }

    public function getCurrentCommit($fqcn)
    {
    	return rtrim(file_get_contents($this->gitDirectory . $fqcn . '/.git/refs/heads/master'));
    }

    public function getTranslationsDirectory($fqcn)
    {
    	return $this->gitDirectory . $fqcn . '/Resources/translations';
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->devTranslator->setLogger($logger);
        $this->logger = $logger;
    }

    public function getLastTranslations($vendor, $bundle, $lang, $currentCommit = true, $page = 1)
    {
        $showAll = $this->authorization->isGranted('ROLE_TRANSLATOR_ADMIN') ? true: false;
        $commit = $this->getCurrentCommit($vendor . $bundle);

        return $this->repository
            ->findLastTranslations($vendor, $bundle, $commit, $lang, $showAll);
    }

    public function searchLastTranslations($vendor, $bundle, $lang, $search, $currentCommit = true, $page = 1)
    {
        $showAll = $this->authorization->isGranted('ROLE_TRANSLATOR_ADMIN') ? true: false;
        $commit = $this->getCurrentCommit($vendor . $bundle);

        $translations = $this->repository
            ->searchLastTranslations($vendor, $bundle, $commit, $lang, $search, $showAll);

        return $this->getLatestFromSearch($translations);
    }

    public function getTranslationItem($vendor, $bundle, $domain, $lang, $key)
    {
        $translations = $this->repository->findBy(array(
            'vendor' => $vendor, 
            'bundle' => $bundle, 
            'lang'   => $lang, 
            'key'    => $key,
            'domain' => $domain
        ), array('id' => 'DESC'));

        return $translations;
    }

    public function addTranslation(TranslationItem $translationItem, $value)
    {
        if ($this->tokenStorage->getToken()) {
            $creator = $this->tokenStorage->getToken()->getUser() !== 'anon.' ? 
                $this->tokenStorage->getToken()->getUser(): null;
        } else {
            $creator = null;
        }

        $translation = new Translation();
        $translation->setCreator($creator);
        $translation->setTranslation($value);
        $translation->setTranslationItem($translationItem);
        $this->om->persist($translation);
        $this->om->flush();

        return $translationItem;
    }

    public function find($translationItemId)
    {
        return $this->repository->find($translationItemId);
    }

    /*
     * Change the implementation to make a dynamic locale list.
     */
    public function getAvailableLocales()
    {
        return array('fr', 'en', 'nl', 'de', 'es');
    }

    public function clickUserLock(TranslationItem $translationItem)
    {
        $translationItem->changeUserLock();
        $this->om->persist($translationItem);
        $this->om->flush();
    }

    public function clickAdminLock(TranslationItem $translationItem)
    {
        $translationItem->changeAdminLock();
        $this->om->persist($translationItem);
        $this->om->flush();
    }

    private function getLatestFromSearch(array $translations)
    {
        $data = array();

        foreach ($translations as $translation) {
            $data[] = $translation->getTranslationItem();
        }

        return $data;
    }

    private function getLatestFromArray(array $translations)
    {
        $last = array();
        $sorted = [];
        //this is way more efficient than the 'NOT EXISTS' for large requests
        foreach ($translations as $translation) {
            $last[$translation->getIndex()] = $translation;
        }

        return array_values($last);       
    }

    private function setDatabaseFromYaml($vendor, $bundle)
    {
        $iterator = new \DirectoryIterator($this->getTranslationsDirectory($vendor . $bundle));
        $commit = $this->getCurrentCommit($vendor . $bundle);

        $this->log('Setting up database...');
        $domains = array();
            
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $parts = explode('.', $fileInfo->getBasename());
                $domain = $parts[0];
                $lang = $parts[1];
                $domains[$domain][] = $lang;
            }
        }

        //used for doctrine transaction
        $_i = 0;
        $this->om->startFlushSuite();

        foreach ($domains as $domain => $langs) {

            $translationMainPath = $this->getTranslationsDirectory($vendor . $bundle) . '/' . $domain . '.fr.yml';

            foreach ($this->getAvailableLocales() as $lang) {
               
                $translationFilePath = $this->getTranslationsDirectory($vendor . $bundle) . '/' . $domain . '.' . $lang . '.yml';
                $this->log('Initializing ' . $translationFilePath . '...');

                if (file_exists($translationMainPath)) {
                    $this->log('Initializing ' . $translationFilePath . '...', LogLevel::DEBUG);
                    $this->devTranslator->fill(
                        $translationMainPath, 
                        $translationFilePath
                    );
                }

                $translations = Yaml::parse($translationFilePath);
                if (!is_array($translations)) $translations = array();

                $this->recursiveParseTranslation(
                    $translations, 
                    $domain, 
                    $lang, 
                    $commit, 
                    $vendor, 
                    $bundle,
                    '', 
                    $_i
                );
            }
        }

        $this->om->endFlushSuite();
    }
}