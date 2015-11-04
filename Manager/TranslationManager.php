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
     *     "gitDirectory" = @DI\Inject("%claroline.param.git_directory%"),
     *	   "om"           = @DI\Inject("claroline.persistence.object_manager"),
     *	   "gitConfig"     = @DI\Inject("%claroline.param.git_config%")
     * })
     */
    public function __construct(
        $gitDirectory,
        $om,
        $gitConfig
    )
    {
        $this->gitDirectory = $gitDirectory;
        $this->om           = $om;
        $this->gitConfig    = $gitConfig;
    }

    public function clear($vendor, $bundle)
    {
    	$this->log('Clearing the database for ' . $vendor . $bundle, LogLevel::DEBUG);
    	$translationItems = $this->om->getRepository('ClarolineTranslatorBundle:TranslationItem')
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
        $_i = 0;
    		
        foreach ($iterator as $fileInfo) {
        	if ($fileInfo->isFile()) {
        		$baseName = $fileInfo->getBasename();
        		$this->log('Initializing ' . $baseName . '...');
        		$translations = Yaml::parse($fileInfo->getPathname());
        		$parts = explode('.', $baseName);
        		$domain = $parts[0];
        		$lang = $parts[1];
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
                    $path . '[' . $key . ']', 
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

    private function getCurrentCommit($fqcn)
    {
    	return rtrim(file_get_contents($this->gitDirectory . $fqcn . '/.git/refs/heads/master'));
    }

    private function getTranslationsDirectory($fqcn)
    {
    	return $this->gitDirectory . $fqcn . '/Resources/translations';
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}