<?php
namespace SabaiApps\Directories\Component\Contact\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class FormHelper
{
    public function help(Application $application, IEntity $entity, $formId)
    {
        if (!$this->isEnabled($application, $entity)) return;

        if (!$this->recipients($application, $entity)) {
            if ($application->getUser()->isAnonymous()) return;

            if (!$application->getUser()->isAdministrator()
                && $entity->getAuthorId() !== $application->getUser()->id
            ) return;

            return sprintf(
                '<div class="%1$salert %1$salert-warning">%2$s</div>',
                DRTS_BS_PREFIX,
                $application->H(__('The contact form is hidden because there are no recipients available.', 'directories-pro'))
            );
        }

        if ($application->getPlatform()->getName() === 'WordPress') {
            if (strpos($formId, 'wpcf7-') === 0) { // Contact Form 7
                if (($parts = explode('-', $formId))
                    && !empty($parts[1])
                ) {
                    return do_shortcode('[contact-form-7 id="' . $this->_maybeGetTranslatedPostId($application, $parts[1], 'wpcf7_contact_form') . '"]');
                }
            } elseif (strpos($formId, 'wpforms-') === 0) { // WPForms
                if (($parts = explode('-', $formId))
                    && !empty($parts[1])
                ) {
                    return do_shortcode('[wpforms id="' . $this->_maybeGetTranslatedPostId($application, $parts[1], 'wpforms') . '" title="false" description="false"]');
                }
            } elseif (strpos($formId, 'gform-') === 0) { // Gravity Form
                if (($parts = explode('-', $formId))
                    && !empty($parts[1])
                ) {
                    return do_shortcode('[gravityform ajax="true" id="' . intval($parts[1]) . '" title="false" description="false"]');
                }
            } elseif (strpos($formId, 'happyforms-') === 0) { // HappyForms
                if (($parts = explode('-', $formId))
                    && isset($parts[1])
                    && ($post_id = intval($parts[1]))
                ) {
                    if (happyforms_get_meta($post_id, 'modal', true)) {
                        return '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-danger ">Directories Pro can not display HappyForms forms in overlay window.</div>';
                    }
                    return do_shortcode('[happyforms id="' . $this->_maybeGetTranslatedPostId($application, $post_id, 'happyforms') . '"]');
                }
            }
        }
    }
    
    protected function _maybeGetTranslatedPostId(Application $application, $id, $bundleName)
    {
        $id = intval($id);
        if (($lang = $application->getPlatform()->getCurrentLanguage())
            && ($translated_entity_id = $application->getPlatform()->getTranslatedId('post', $bundleName, $id, $lang))
        ) {
           $id = $translated_entity_id;  
        }
        return $id;
    }
    
    public function options(Application $application)
    {
        $options = [];

        if ($application->getPlatform()->getName() === 'WordPress') {
            // Contact Form 7
            if (defined('WPCF7_VERSION')
                && ($wpcf7_forms = get_posts(['post_type' => 'wpcf7_contact_form', 'post_status' => 'publish', 'posts_per_page' => -1]))
            ) {
                foreach ($wpcf7_forms as $post) {
                    $options['wpcf7-' . $post->ID] = 'Contact Form 7 - ' . $post->post_title;
                }
            }
            // WPForms
            if (function_exists('wpforms')) {
                $wpforms_forms = wpforms()->form->get('', ['orderby' => 'title']);
                if (!empty($wpforms_forms)) {
                    foreach ($wpforms_forms as $post) {
                        $options['wpforms-' . $post->ID] = 'WPForms - ' . $post->post_title;
                    }
                }
            }
            // Gravity Form
            if (class_exists('\GFAPI', false)
                && ($gravity_forms = \GFAPI::get_forms())
            ) {
                foreach ($gravity_forms as $form) {
                    $options['gform-' . $form['id']] = 'Gravity Form - ' . $form['title'];
                }
            }
            // HappyForms
            if (defined('HAPPYFORMS_VERSION')
                && ($happyforms = get_posts(['post_type' => 'happyform', 'post_status' => 'publish', 'posts_per_page' => -1]))
            ) {
                foreach ($happyforms as $post) {
                    $options['happyforms-' . $post->ID] = 'HappyForms - ' . $post->post_title;
                }
            }
        }

        
        return $options;
    }

    public function recipients(Application $application, IEntity $entity)
    {
        $recipients = [];
        if (!$application->isComponentLoaded('Payment')
            || (!$bundle = $application->Entity_Bundle($entity))
            || empty($bundle->info['payment_enable'])
        ) {
            // Payment is not enabled

            if (!isset($bundle)) $bundle = $application->Entity_Bundle($entity);
            if (!$bundle
                || !isset($bundle->info['contact_form']['recipients'])
            ) {
                // Invalid bundle or no contact form settings, send to author
                if (($author = $application->Entity_Author($entity))
                    && $author->email
                ) {
                    $recipients['author'] = $author->email;
                }
            } else {
                $recipients = $this->_getRecipientsFromSettings($application, $bundle->info['contact_form']['recipients'], $entity);
            }
        } else {
            // Payment is enabled

            if ($application->Payment_Plan_hasFeature($entity, 'contact_form')
                && ($features = $application->Payment_Plan_features($entity))
            ) {
                if (!empty($features[0]['contact_form']['recipients'])) { // base feature
                    $recipients = $this->_getRecipientsFromSettings($application, $features[0]['contact_form']['recipients'], $entity);
                } elseif (!empty($features[1]['contact_form']['recipients'])) { // add-on feature
                    $recipients = $this->_getRecipientsFromSettings($application, $features[1]['contact_form']['recipients'], $entity);
                }
            }
        }

        if ($recipients = $application->Filter('contact_email_recipients', $recipients, [$entity])) {
            $recipients_normalized = [];
            foreach (array_keys($recipients) as $key) {
                if (is_array($recipients[$key])) {
                    foreach (array_keys($recipients[$key]) as $_key) {
                        $recipients_normalized[] = $recipients[$key][$_key];
                    }
                } else {
                    $recipients_normalized[] = $recipients[$key];
                }
            }
            $recipients = array_unique($recipients_normalized);
        }

        return $recipients;
    }

    protected function _getRecipientsFromSettings(Application $application, array $settings, IEntity $entity)
    {
        $recipients = [];
        foreach ($settings as $recipient) {
            if ($recipient === 'author') {
                if (($author = $application->Entity_Author($entity))
                    && $author->email
                ) {
                    $recipients[$recipient] = $author->email;
                }
            } elseif ($recipient === 'site') {
                if ($site_email = $application->SiteInfo('email')) {
                    $recipients[$recipient] = $site_email;
                }
            } else {
                if (($field = $application->Entity_Field($entity, $recipient))
                    && ($field_type = $application->Field_Type($field->getFieldType(), true))
                    && $field_type instanceof \SabaiApps\Directories\Component\Field\Type\IEmail
                    && ($field_email = $field_type->fieldEmailAddress($field, $entity, false))
                ) {
                    $recipients[$recipient] = $field_email;
                }
            }
        }
        return $recipients;
    }

    public function isEnabled(Application $application, IEntity $entity)
    {
        if ($entity->isTaxonomyTerm()) return false;

        if (!$application->isComponentLoaded('Payment')) return true;

        if ((!$bundle = $application->Entity_Bundle($entity))
            || !empty($bundle->info['parent'])
        ) return false;

        return empty($bundle->info['payment_enable'])
            || $application->Payment_Plan_hasFeature($entity, 'contact_form');
    }
}