<?php
namespace SabaiApps\Directories\Component\Contact;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class ContactComponent extends AbstractComponent implements Display\IElements
{
    const VERSION = '1.3.108', PACKAGE = 'directories-pro';
    
    public static function interfaces()
    {
        return array('Payment\IFeatures');
    }
    
    public static function description()
    {
        return 'Allows visitors to send messages to content authors/owners through a contact form.';
    }
    
    public function displayGetElementNames(Entity\Model\Bundle $bundle)
    {
        if (!empty($bundle->info['is_taxonomy'])
            || !empty($bundle->info['parent'])
        ) return [];
        
        return array('contact_form');
    }
    
    public function displayGetElement($name)
    {
        return new DisplayElement\FormDisplayElement($this->_application, $name);
    }
    
    public function paymentGetFeatureNames()
    {
        return array('contact_form');
    }
    
    public function paymentGetFeature($name)
    {
        return new PaymentFeature\FormPaymentFeature($this->_application, $name);
    }
        
    public function onCorePlatformWordPressInit()
    {
        // Contact Form 7
        if (defined('WPCF7_VERSION')) {
            add_filter('wpcf7_form_hidden_fields', array($this, 'wpcf7FormHiddenFieldsFilter'));
            add_filter('wpcf7_mail_components', array($this, 'wpcf7MailComponentsFilter'));
            // Need to manually populate values for post special mail tags as they work when the form is inside the post content only.
            add_filter('wpcf7_special_mail_tags', [$this, 'wpcf7SpecialMailTagsFilter'], 10, 3);
        }
        // WPForms
        if (defined('WPFORMS_VERSION')) {
            add_action('wpforms_frontend_output', array($this, 'wpformsFrontendOutputAction'));
            add_filter('wpforms_entry_email_atts', array($this, 'wpformsEntryEmailAttsFilter'));
        }
        // Gravity Form
        if (class_exists('\GFForms', false)) {
            add_filter('gform_pre_render', array($this, 'gformPreRenderFilter'));
            add_filter('gform_replace_merge_tags', array($this, 'gformReplaceMergeTagsFilter'));
        }
        // HappyForms
        if (defined('HAPPYFORMS_VERSION')) {
            add_filter('happyforms_email_alert', [$this, 'happyformsEmailAlertFilter']);
        }
    }

    public function happyformsEmailAlertFilter($message)
    {
        if ($recipients = $this->_isSending()) {
            $message->set_to(implode(',', $recipients));
        }
        return $message;
    }
    
    public function gformPreRenderFilter($form)
    {
        if ($entity = $this->_isDisplaying()) {
            array_push($form['fields'], \GF_Fields::create(array(
                'type' => 'html',
                'content' => '<input type="hidden" name="_drts_contact_entity_id" value="' . intval($entity->getId()) . '" />',
            )));   
        }
        return $form;
    }
    
    public function gformReplaceMergeTagsFilter($text)
    {
        $tag = '{drts_contact_recipients}';
        if (strpos($text, $tag) !== false) {        
            $text = str_replace($tag, ($recipients = $this->_isSending()) ? implode(',', $recipients) : '', $text);
        }
        return $text;
    }
    
    public function wpformsFrontendOutputAction()
    {
        if ($entity = $this->_isDisplaying()) {
            echo '<input type="hidden" name="_drts_contact_entity_id" value="' . intval($entity->getId()) . '" />';
        }
    }
    
    public function wpformsEntryEmailAttsFilter($email)
    {
        if ($recipients = $this->_isSending()) {
            $email['address'] = $recipients;
        }
        return $email;
    }
        
    public function wpcf7FormHiddenFieldsFilter($fields)
    {
        if ($entity = $this->_isDisplaying()) {
            $fields['_drts_contact_entity_id'] = $entity->getId();
        }
        return $fields;
    }
    
    public function wpcf7MailComponentsFilter($components)
    {
        if ($recipients = $this->_isSending()) {
            $components['recipient'] = implode(',', $recipients);
        }
        return $components;
    }

    public function wpcf7SpecialMailTagsFilter($output, $name, $html)
    {
        if (strpos($name, '_post_') === 0
            && ($entity = $this->_isSending(true))
            && ($post = $entity->post())
        ) {
            switch ($name) {
                case '_post_id':
                    return (string)$post->ID;
                case '_post_name':
                    return $post->post_name;
                case '_post_title':
                    return $html ? esc_html($post->post_title) : $post->post_title;
                case '_post_url':
                    return get_permalink($post->ID);
                case '_post_author':
                case '_post_author_email':
                    $user = new \WP_User($post->post_author);
                    return $name === '_post_author' ? $user->display_name : $user->user_email;
            }
        }
        return $output;
    }
    
    protected function _isDisplaying()
    {
        return (($entity = $this->_getCurrentEntity())
            && !$entity->isTaxonomyTerm()
            && Display\Helper\RenderHelper::isRendering($entity->getBundleName(), 'detailed')
            && $this->_application->Contact_Form_isEnabled($entity)
        ) ? $entity : false;
    }

    protected function _getCurrentEntity()
    {
        if (isset($GLOBALS['drts_entity'])) return $GLOBALS['drts_entity'];

        return $this->_application->Filter('field_current_entity', null);
    }
    
    protected function _isSending($returnEntity = false)
    {
        if (!empty($_REQUEST['happyforms_form_id'])) { // Workaround for HappyForms
            if (empty($_REQUEST['referer'])
                || (!$slug = basename($_REQUEST['referer']))
                || (!$happyforms = $this->_application->getPlatform()->getCache('contact_happyforms'))
                || (!$form_id = intval($_REQUEST['happyforms_form_id']))
                || empty($happyforms[$form_id])
            ) return false;

            $entity = null;
            foreach (array_keys($happyforms[$form_id]) as $bundle_name) {
                if ($entity = $this->_application->Entity_Entity('post', is_numeric($slug) ? $slug : [$bundle_name, $slug])) break;
            }
            if (!$entity) return false;
        } else {
            if (empty($_POST['_drts_contact_entity_id'])
                || (!$entity = $this->_application->Entity_Entity('post', $_POST['_drts_contact_entity_id']))
            ) return false;
        }
        if ($entity->isTaxonomyTerm()) return false;

        if ($returnEntity) return $entity;

        return ($recipients = $this->_application->Contact_Form_recipients($entity)) ? $recipients : false;
    }
    
    public function onEntityBundleSettingsFormFilter(&$form, $bundle, $submitValues)
    {
        if (!$this->_application->Entity_BundleTypeInfo($bundle, 'contact_enable')) return;
        
        if ($this->_application->isComponentLoaded('Payment')
            && !empty($bundle->info['payment_enable'])
        ) return; // the setting is integrated with payment feature settings

        $value = empty($bundle->info['contact_form']) ? [] : $bundle->info['contact_form'];
        $recipient_options = $this->_application->getComponent('Contact')->getRecipientOptions($bundle);
        $form['general']['contact_form'] = [
            '#title' => __('Contact Form', 'directories-pro'),
            '#tree' => true,
            '#weight' => 99,
            'recipients' => [
                '#title' => __('Contact form recipients', 'directories-pro'),
                '#type' => 'checkboxes',
                '#options' => $recipient_options[0],
                '#options_disabled' => $recipient_options[1],
                '#default_value' => isset($value['recipients']) ? $value['recipients'] : (in_array('author', $recipient_options[0]) ? ['author'] : null),
                '#horizontal' => true,
                '#columns' => 1,
            ],
        ];
    }
    
    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'contact_form';
    }
    
    public function getRecipientOptions(Entity\Model\Bundle $bundle)
    {
        $options = [
            'site' => __('Site E-mail', 'directories-pro') . ' - ' . $this->_application->SiteInfo('email'),
        ];
        if (empty($bundle->info['is_user'])) {
            $options['author'] = __('Author', 'directories-pro');
        }
        $email_field_options = $this->_application->Entity_Field_options($bundle, [
            'interface' => 'Field\Type\IEmail',
            'prefix' => __('Field - ', 'directories-pro'),
            'return_disabled' => true,
        ]);
        $options += $email_field_options[0];        
        return [$options, array_keys($email_field_options[1])];
    }
}
