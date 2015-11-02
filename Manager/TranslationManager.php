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

    	foreach ($translationsItems as $item) {
    		$this->om->remove($item);
    	}

    	$this->om->flush();
    	$this->log('Removing commit from config file...');

	    if ($configs = file_exists($this->gitConfig)) {
	    	unset($configs[$vendor . $bundle]);
	    	file_put_contents($this->gitConfig, Yaml::dump($configs));
	    }
    }

    public function init($vendor, $bundle)
    {
    	$this->log('Setting up git config for ' . $vendor . $bundle . ' in ' . $this->gitConfig);

        $iterator = new \DirectoryIterator($this->getTranslationsDirectory($vendor . $bundle));
        $commit = $this->getCurrentCommit($vendor . $bundle);
        $configs = file_exists($this->gitConfig) ? Yaml::parse($this->gitConfig): array();
        $configs[$vendor . $bundle] = $commit;

        if (!file_put_contents($this->gitConfig, Yaml::dump($configs))) {
        	$this->log("Couldn't add git config !!!", LogLevel::DEBUG);
        }

        $this->log('Setting up database...');
        $i = 0;
    		
        foreach ($iterator as $fileInfo) {
        	if ($fileInfo->isFile()) {
        		$baseName = $fileInfo->getBasename();
        		$this->log('Initializing ' . $baseName . '...');
        		$translations = Yaml::parse($fileInfo->getPathname());
        		$parts = explode('.', $baseName);
        		$domain = $parts[0];
        		$lang = $parts[1];

    			//no sub domains here yet
        		foreach ($translations as $key => $translation) {
        			$i++;
        			$item = new TranslationItem();
        			$item->setKey($key);
        			$item->setTranslation($translation);
        			$item->setDomain($domain);
        			$item->setLang($lang);
        			$item->setCommit($commit);
        			$item->setVendor($vendor);
        			$item->setBundle($bundle);
        			$this->om->persist($item);    	

        			if ($i % self::BATCH_SIZE === 0) {
        				$this->log('Flushing ' . $i . ' items...');
        				$this->om->flush();
        			}
        		}
        	}
        }

        $this->om->flush();
    }

    private function getCurrentCommit($fqcn)
    {
    	return file_get_contents($this->gitDirectory . $fqcn . '/.git/refs/heads/master');
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