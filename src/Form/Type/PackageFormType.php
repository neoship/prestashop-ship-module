<?php
namespace Neoship\Form\Type;

use PrestaShopBundle\Form\Admin\Type\CommonAbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Neoship\Entity\Package;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackageFormType extends CommonAbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, [
                'label' => false
            ])
            ->add('variablenumber', TextType::class, [
                'label' => false
            ])
            ->add('sms', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('phone', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('email', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('cod', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('saturday', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('attachment', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('holddelivery', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('codprice', NumberType::class, [
                'label' => false,
            ])
            ->add('parts', NumberType::class, [
                'label' => false,
            ])
            ->add('insurance', NumberType::class, [
                'label' => false,
            ])
            ->add('delivery', ChoiceType::class, [
                'label' => false,
                'choices'  => [
                    'Standard delivery' => 0,
                    'Express 12' => 1,
                    'Express 9' => 2,
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Package::class,
            'translation_domain' => 'Modules.Neoship.view'
        ]);
    }
}