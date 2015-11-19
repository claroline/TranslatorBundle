<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Accessor;

/**
 * @ORM\Entity
 * @ORM\Table(name="claro__git_translation")
 */
class Translation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="translation", type="text")
     * @Groups({"translator", "infos"})
     */
    protected $translation;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\TranslatorBundle\Entity\TranslationItem",
     *     inversedBy="translations",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="translation_item_id", onDelete="CASCADE", nullable=false)
     */
    protected $translationItem;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    protected $creator;

    /**  
     * @SerializedName("author")
     * @Accessor(getter="getAuthor")
     * @Groups({"infos"})
     */
    protected $author;

    /**
     * @ORM\Column(name="creation_date", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $creationDate;

    /**
     * @Groups({"infos"})
     * @SerializedName("creation_date")
     * @Accessor(getter="getDateAsString")
     */
    protected $dateAsString;

    public function getId()
    {
        return $this->id;
    }

    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }

    public function getTranslation()
    {
        return $this->translation;
    }

    public function setTranslationItem($translationItem)
    {
        $this->translationItem = $translationItem;
    }

    public function getTranslationItem()
    {
        return $this->translationItem;
    }

    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getAuthor()
    {
        return $this->creator ? $this->creator->getUsername(): 'claroline';
    }

    public function setCreationDate(\DateTime $creationDate)
    {
        $this->creationDate = $creationDate;
    }

    public function getCreationDate()
    {
        return $this->creationDate;
    }

    public function getDateAsString()
    {
        return $this->creationDate;
        //return $this->creationDate->format('m/d/Y');
    }
} 