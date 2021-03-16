<?php

namespace Neoship\Grid\Action\Type;

use PrestaShop\PrestaShop\Core\Grid\Action\Row\AbstractRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\AccessibilityChecker\AccessibilityCheckerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * It extends AbstractRowAction,
 * but you can also implement \PrestaShop\PrestaShop\Core\Grid\Action\RowActionInterface 
 * if for some reason you want to avoid using the abstract class
 */ 
final class TrackingRowAction extends AbstractRowAction
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'neoship_tracking';
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired([
                'route',
                'route_param_name',
                'route_param_field',
            ])
            ->setDefaults([
                'confirm_message' => '',
                'accessibility_checker' => null,
                'extra_route_params' => [],
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('route_param_name', 'string')
            ->setAllowedTypes('route_param_field', 'string')
            ->setAllowedTypes('extra_route_params', 'array')
        ;
    }
}