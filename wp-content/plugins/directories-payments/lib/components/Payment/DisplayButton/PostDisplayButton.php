<?php
namespace SabaiApps\Directories\Component\Payment\DisplayButton;

use SabaiApps\Directories\Component\Dashboard;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class PostDisplayButton extends Dashboard\DisplayButton\AbstractPostDisplayButton
{    
    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name, substr($name, strlen('payment_')));
    }
    
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'payment_renew':
                return array(
                    'label' => __('Renew item button', 'directories-payments'),
                    'labellable' => false,
                    'default_settings' => array(
                        '_color' => 'outline-secondary',
                        '_icon' => 'fas fa-sync',
                    ),
                    'overlayable' => false,
                );
            case 'payment_upgrade':
                return array(
                    'label' => __('Upgrade/downgrade item button', 'directories-payments'),
                    'labellable' => false,
                    'default_settings' => array(
                        '_color' => 'outline-secondary',
                        '_icon' => 'fas fa-arrows-alt-v',
                    ),
                    'overlayable' => false,
                );
            case 'payment_order_addon':
                return array(
                    'label' => __('Order add-on button', 'directories-payments'),
                    'labellable' => false,
                    'default_settings' => array(
                        '_color' => 'ouline-secondary',
                        '_icon' => 'fas fa-cart-plus',
                    ),
                    'overlayable' => false,
                );
        }
        
    }
    
    protected function _getLabel(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        switch ($this->_name) {
            case 'payment_renew':
                return __('Renew', 'directories-payments');
            case 'payment_upgrade':
                return __('Upgrade / Downgrade', 'directories-payments');
            case 'payment_order_addon':
                return __('Order Add-on', 'directories-payments');
        }
    }
}