<?php
/**
 * This file is part of Organist
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author: markri <mdekrijger@netvlies.nl>
 */

namespace Netvlies\Bundle\PublishBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class TargetStep2Type extends HorizontalAbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', 'text', array(
                'label'=>'Label *',
                'attr' => array('data-help' => 'e.g. "(P) www.myapp.com"'),
                'required'=>true)
            )
            ->add('primaryDomain', 'text', array(
                'label'=>'Domain',
                'attr' => array('data-help' => 'e.g. "www.myapp.com"'),
                'required'=>false)
            )
            ->add('domainAliases', 'onetomany', array(
                    'label' => 'Domain aliases',
                    'type' => new DomainAliasType(),
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'prototype_name' => '__name__')
            )
            ->add('mysqldb', 'text', array(
                'required'=>false,
                'label'=>'MySQL database')
            )
            ->add('mysqluser', 'text', array(
                'required'=>false,
                'label'=>'MySQL user')
            )
            ->add('mysqlpw', 'text', array(
                'required'=>false,
                'label'=>'MySQL password')
            )
            ->add('approot', 'text', array(
                'required'=>true,
                'label'=>'Absolute approot *')
            )
            ->add('webroot', 'text', array(
                'required'=>true,
                'label'=>'Absolute webroot *')
            )
            ->add('caproot', 'text', array(
                'required'=>false,
                'label'=>'Absolute capistrano root')
            );
    }


    public function getName()
    {
        return 'netvlies_publishbundle_target_step2';
    }

}