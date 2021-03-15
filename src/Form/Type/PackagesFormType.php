<?php
namespace Neoship\Form\Type;

use PrestaShopBundle\Form\Admin\Type\CommonAbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Neoship\Form\Type\PackageFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Neoship\Entity\Packages;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PackagesFormType extends CommonAbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('packages', CollectionType::class, [
            'entry_type' => PackageFormType::class,
            'entry_options' => ['label' => false],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Packages::class,
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return ''; // return an empty string here
    }

}


