<?php

namespace Oylex\SpamJudgeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TextSampleType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'sample',
                'textarea',
                array(
                    'required' => true,
                )
            )
            ->add(
                'ip',
                'text',
                array(
                    'required' => false,
                )
            )
            ->add(
                'userAgent',
                'text',
                array(
                    'required' => false,
                )
            )
            ->add(
                'referrer',
                'text',
                array(
                    'required' => false,
                )
            )
            //->add('type')
            //->add('tokenProcessed')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'        => 'Oylex\SpamJudgeBundle\Entity\TextSample',
            'csrf_protection'   => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
