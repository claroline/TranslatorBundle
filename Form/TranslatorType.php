<?php
/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\TranslatorBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TranslatorType extends AbstractType
{
    public function __construct($categoryId = null, $allowUsers = false, $username = false)
    {
        $this->categoryId = $categoryId;
        $this->allowUsers = $allowUsers;
        $this->username   = $username;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'category',
            'integer',
            array(
                'label' => 'category',
                'data' => $this->categoryId
            )
        );
        $builder->add(
            'allowUsers',
            'checkbox',
            array(
                'label' => 'allow_users_to_translate',
                'required' => false,
                'data' => $this->allowUsers
            )
        );
        $builder->add(
            'git_username',
            'text',
            array(
                'label' => 'github login',
                'required' => false,
                'data' => $this->username
            )
        );
    }

    public function getName()
    {
        return 'translator_type_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'translator'));
    }
}
