<?php
namespace SabaiApps\Directories\Component\reCAPTCHA\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class CaptchaDisplayElement extends Display\Element\AbstractElement
{    
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => 'reCAPTCHA',
            'description' => __('Show a CAPTCHA field with reCAPTCHA API', 'directories-frontend'),
            'default_settings' => [],
            'icon' => 'fas fa-shield-alt',
            'designable' => ['margin'],
        );
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return in_array($display->type, array('form'))
            && empty($bundle->info['internal']);
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if ($this->_application->getPlatform()->isAdmin()
            || isset($var['#entity']) // do not show if editing entity
        ) return;
        
        return $var->render()->getHtml('recaptcha', $var->settings['#wrap']);
    }
    
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element)
    {
        return true;
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, &$var)
    {
        if (isset($var['#entity'])) return; // do not show if editing entity
        
        // Inject form into a variable so that it can be obtained on render
        $var['recaptcha'] = $this->_application->reCAPTCHA_Captcha(array(
            'name' => $bundle->name . '-' . $element['id'],
        ));
    }
}
