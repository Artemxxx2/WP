<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception\RuntimeException;

class AddDisplay extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $display_type = $context->getRequest()->asStr('display_type');
        $display_name = $context->getRequest()->asStr('display_name');
        $this->_ajaxSubmit = true;
        $this->_ajaxOnSuccessRedirect = true;
        $this->_submitButtons[] = [
            '#btn_color' => 'success',
            '#btn_size' => 'lg',
        ];
        $name_prefix = $display_type === 'entity' ? $name_prefix = $display_name . '-' : null;
        $form = [
            'method' => [
                '#type' => 'select',
                '#title' => __('Create or copy', 'directories'),
                '#horizontal' => true,
                '#options' => [
                    '' => _x('Create empty', 'create empty display', 'directories'),
                    'existing' => __('Copy from existing', 'directories'),
                ],
                '#default_value' => '',
                '#required' => true,
                '#weight' => 1,
            ],
            'existing' => [
                '#type' => 'select',
                '#options' => self::existingDisplays($this->_application, $context->bundle->name, $display_name, $display_type),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        '[name="method"]' => ['value' => 'existing'],
                    ],
                ],
                '#weight' => 5,
            ],
            'name' => [
                '#type' => 'textfield',
                '#title' => __('Name', 'directories'),
                '#description' => __('Enter a unique name so that that it can be easily referenced. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
                '#field_prefix' => $name_prefix,
                '#max_length' => 100 - strlen($name_prefix),
                '#required' => true,
                '#regex' => '/^[a-z0-9_]+$/',
                '#horizontal' => true,
                '#element_validate' => [
                    [[$this, '_validateName'], [$context->bundle, $display_type, $name_prefix]],
                ],
                '#weight' => 10,
            ],
            'display_type' => [
                '#type' => 'hidden',
                '#value' => $context->getRequest()->asStr('display_type'),
            ],
            'display_name' => [
                '#type' => 'hidden',
                '#value' => $context->getRequest()->asStr('display_name'),
            ],
        ];
        if ($templates = self::templateDisplayNames($this->_application, $context->bundle, $display_name, $display_type)) {
            $form['method']['#options']['template'] = __('Copy from template', 'directories');
            $form['template'] = [
                '#type' => 'select',
                '#options' => $templates,
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        '[name="method"]' => ['value' => 'template'],
                    ],
                ],
                '#weight' => 3,
            ];
        }
        return $form;
    }

    public function _validateName(Form\Form $form, &$value, $element, $bundle, $type, $prefix)
    {
        $query = $this->getModel('Display', 'Display')
            ->bundleName_is($bundle->name)
            ->type_is($type)
            ->name_is(strlen($prefix) ? $prefix . $value : $value);
        if ($query->count()) {
            $form->setError(__('The name is already taken.', 'directories'), $element);
        }
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $display_type = $context->getRequest()->asStr('display_type');
        $display_name = $context->getRequest()->asStr('display_name');
        $new_display_name = $display_type === 'entity' ? $display_name . '-' . $form->values['name'] : $form->values['name'];

        // Fetch display settings
        $display = [];
        switch ($form->values['method']) {
            case 'template':
                $displays = self::templateDisplays($this->_application, $context->bundle, $display_name, $display_type);
                if (isset($displays[$form->values['template']])) {
                    $display = $displays[$form->values['template']];
                }
                break;
            case 'existing':
                $existing_display_name = $form->values['existing'];
                $existing_display = $this->getModel('Display', 'Display')
                    ->bundleName_is($context->bundle->name)
                    ->type_is($display_type)
                    ->name_is($existing_display_name)
                    ->fetchOne();
                if (!$existing_display) {
                    throw new RuntimeException('Invalid display: ' . $existing_display_name);
                }
                $display = $this->Display_Display_export($existing_display);
                if (isset($display['data']['css'])
                    && strlen($display['data']['css'])
                ) {
                    // Replace CSS class specific to the existing display with the CSS class of the new display
                    $display['data']['css'] = str_replace(
                        '.' . Display::cssClass($existing_display->name, $existing_display->type),
                        '.' . Display::cssClass($new_display_name, $display_type),
                        $display['data']['css']
                    );
                }
                break;
            default:
        }

        // Create display
        $this->Display_Create($context->bundle, $display_type, $new_display_name, $display);

        // Send success
        $admin_path = strtr($this->_application->Entity_BundleTypeInfo($context->bundle, 'admin_path'), [
            ':bundle_name' => $context->bundle->name,
            ':directory_name' => $context->bundle->group,
            ':bundle_group' => $context->bundle->group,
        ]);
        switch ($display_type) {
            case 'filters':
                $admin_path .= '/views/filters';
                break;
            case 'form':
                $admin_path .= '/fields';
                break;
            default:
                $admin_path .= '/displays';
                break;
        }
        $context->setSuccess($this->Url($admin_path, [], 'drts-display-tab2-' . $new_display_name));
    }

    public static function templateDisplays(Application $application, $bundle, $defaultDisplayName, $type = 'entity')
    {
        $bundles_info = $application->Filter(
            'entity_bundle_info',
            [$bundle->type => $application->Entity_BundleTypeInfo($bundle->type, null, false)],
            [$bundle->component, $bundle->group]
        );
        $bundle_info = $bundles_info[$bundle->type];
        unset($bundles_info);
        if (empty($bundle_info['displays'])) return [];

        if (is_string($bundle_info['displays'])) {
            if (file_exists($bundle_info['displays'])) {
                $displays = include $bundle_info['displays'];
            }
        } else {
            $displays = $bundle_info['displays'];
        }
        if (empty($displays[$type])) return [];

        $displays = $displays[$type];
        foreach (array_keys($displays) as $display_name) {
            if (0 !== strpos($display_name, $defaultDisplayName)) {
                unset($displays[$display_name]);
                continue;
            }
        }
        return $displays;
    }

    public static function templateDisplayNames(Application $application, $bundle, $defaultDisplayName, $type = 'entity')
    {
        $displays = self::templateDisplays($application, $bundle, $defaultDisplayName, $type);
        $prefix = $type === 'entity' ? $defaultDisplayName . '-' : null;
        foreach (array_keys($displays) as $display_name) {
            if ($display_name === $defaultDisplayName) {
                $displays[$display_name] = __('Default', 'directories');
            } else {
                $displays[$display_name] = substr($display_name, strlen($prefix))
                    . ' (' . $display_name .  ')';
            }
        }
        return $displays;
    }

    public static function existingDisplays(Application $application, $bundleName, $defaultDisplayName, $type = 'entity')
    {
        $ret = [$defaultDisplayName => __('Default', 'directories')];
        $prefix = $type === 'entity' ? $defaultDisplayName . '-' : null;
        $displays = $application->getModel('Display', 'Display')
            ->bundleName_is($bundleName)
            ->type_is($type);
        if (strlen($prefix)) {
            foreach ($displays->name_startsWith($prefix)->fetch(0, 0, 'name') as $display) {
                $ret[$display->name] = substr($display->name, strlen($prefix))
                    . ' (' . $display->name .  ')';
            }
        } else {
            foreach ($displays->fetch(0, 0, 'name') as $display) {
                if ($display->name === 'default') continue;

                $ret[$display->name] = $display->name;
            }
        }

        return $ret;
    }
}
