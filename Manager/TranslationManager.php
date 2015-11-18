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

    const BATCH_SIZE = 500;

    /**
     * @DI\InjectParams({
     *     "gitDirectory"  = @DI\Inject("%claroline.param.git_directory%"),
     *	   "om"            = @DI\Inject("claroline.persistence.object_manager"),
     *	   "gitConfig"     = @DI\Inject("%claroline.param.git_config%"),
     *     "tokenStorage"  = @DI\Inject("security.token_storage"),
     *     "devTranslator" = @DI\Inject("claroline.dev_manager.translation_manager")
     * })
     */
    public function __construct(
        $gitDirectory,
        $om,
        $gitConfig,
        $tokenStorage,
        $devTranslator
    )
    {
        $this->gitDirectory  = $gitDirectory;
        $this->om            = $om;
        $this->gitConfig     = $gitConfig;
        $this->repository    = $om->getRepository('ClarolineTranslatorBundle:TranslationItem');
        $this->tokenStorage  = $tokenStorage;
        $this->devTranslator = $devTranslator;
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
        /* Not working properly
    	$this->log('Removing commit from config file...');

	    if ($configs = file_exists($this->gitConfig)) {
	    	unset($configs[$vendor . $bundle]);
	    	file_put_contents($this->gitConfig, Yaml::dump($configs, 2));
	    }*/
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

        $this->om->flush();
    }

    /*
     *
     */
    private function recursiveParseTranslation(
        $translations,
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
                    $path . '' . $key . ']', 
                    $_i
                );
            }

            $_i++;
            $item = new TranslationItem();
            $item->setKey($path . '[' . $key . ']');
            $item->setTranslation($value);
            $item->setDomain($domain);
            $item->setLang($lang);
            $item->setCommit($commit);
            $item->setVendor($vendor);
            $item->setBundle($bundle);
            $this->om->persist($item);      

            if ($_i % self::BATCH_SIZE === 0) {
                $this->log('Flushing ' . $_i . ' items...');
                $this->om->flush();
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
        $commit = $this->getCurrentCommit($vendor . $bundle);

        $translations = $this->repository
            ->findLastTranslations($vendor, $bundle, $commit, $lang);

        return $this->getLatestFromArray($translations);
    }

    public function searchLastTranslations($vendor, $bundle, $lang, $search, $currentCommit = true, $page = 1)
    {
        $commit = $this->getCurrentCommit($vendor . $bundle);

        $translations = $this->repository
            ->searchLastTranslations($vendor, $bundle, $commit, $lang, $search);

        return $this->getLatestFromArray($translations);
    }

    public function getTranslationInfo($vendor, $bundle, $lang, $key)
    {
        $translations = $this->repository->findBy(array(
            'vendor' => $vendor, 
            'bundle' => $bundle, 
            'lang'   => $lang, 
            'key'    => $key
        ));

        return $translations;
    }

    public function addTranslation($vendor, $bundle, $domain, $lang, $key, $value)
    {
        $creator = $this->tokenStorage->getToken()->getUser() !== 'anon.' ? 
            $this->tokenStorage->getToken()->getUser(): null;
        $item = new TranslationItem();
        $item->setVendor($vendor);
        $item->setBundle($bundle);
        $item->setLang($lang);
        $item->setKey($key);
        $item->setTranslation($value);
        $item->setDomain($domain);
        $item->setCommit($this->getCurrentCommit($vendor . $bundle));
        $item->setCreator($creator);
        $this->om->persist($item);
        $this->om->flush();

        return $item;
    }

    /*
     * Change the implementation to make a dynamic locale list.
     */
    public function getAvailableLocales()
    {
        return array('fr', 'en', 'nl', 'de', 'es');
    }

    public function clickUserLockAction($vendor, $bundle, $lang, $key)
    {
        $translations = $this->getTranslationInfo($vendor, $bundle, $lang, $key);

        foreach ($translations as $translation) {
            $translation->changeUserLock();
            $this->om->persist($translation);
        }

        $this->om->flush();
    }

    public function clickAdminLockAction($vendor, $bundle, $lang, $key)
    {
        $translations = $this->getTranslationInfo($vendor, $bundle, $lang, $key);

        foreach ($translations as $translation) {
            $translation->changeAdminLock();
            $this->om->persist($translation);
        }

        $this->om->flush();
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
}