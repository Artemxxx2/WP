<?php
namespace SabaiApps\Directories\Component\DirectoryPro\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form\Form;

class CustomTaxonomiesHelper
{
    public function help(Application $application)
    {
        return (array)$application->getComponent('DirectoryPro')->getConfig('custom_taxonomies');
    }

    protected function _getActiveCustomTaxonomyBundles(Application $application, $customTaxonomyNames)
    {
        $bundles = $application->Entity_Bundles_byType(array_map(function($k){return 'directory_custom_tax_' . $k;}, $customTaxonomyNames), 'Directory');
        $bundle_names = [];
        foreach (array_keys($bundles) as $bundle_name) {
            $bundle_type = $bundles[$bundle_name]->type;
            $bundle_names[substr($bundle_type, strlen('directory_custom_tax_'))][$bundles[$bundle_name]->group] = $bundle_name;
        }
        return $bundle_names;
    }

    public function settingsForm(Application $application, array $settings)
    {
        $application->getPlatform()->addJsFile('directorypro-admin-custom-taxonomies.min.js', 'drts-directorypro-admin-custom-taxonomies', 'drts', 'directories-pro');

        // Add dummy template row
        $settings = [
            '_' => [
                'label' => '',
                'label_singular' => '',
                'icon' => '',
            ],
        ] + $settings;
        $custom_taxonomies = [];
        $delete_link_class = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-link';
        $delete_link_icon = '<i class="fas fa-trash fa-fw"></i>';
        $delete_link = '<span class="' . $delete_link_class . ' ' . DRTS_BS_PREFIX . 'text-danger drts-directorypro-delete-custom-taxonomy">' . $delete_link_icon . '</span>';
        $delete_link_disabled = '<span rel="sabaitooltip" class="' . $delete_link_class . ' ' . DRTS_BS_PREFIX . 'text-muted" style="cursor:not-allowed;" title="'
            . $application->H(__('This custom taxonomy is used by one or more directories (%s) and may not be deleted.'))
            . '">' . $delete_link_icon . '</span>';
        $active_custom_taxonomy_bundles = $this->_getActiveCustomTaxonomyBundles($application, array_keys($settings));
        foreach ($settings as $custom_taxonomy_name => $custom_taxonomy) {
            if (empty($custom_taxonomy['hierarchical'])) {
                $icon = 'tag';
            } else {
                $icon = 'folder';
            }
            $custom_taxonomies[$custom_taxonomy_name] = [
                'name' => '<span class="">' . $custom_taxonomy_name . '</span>',
                'label_singular' => $custom_taxonomy['label_singular'],
                'label' => $custom_taxonomy['label'],
                'icon' => $custom_taxonomy['icon'],
                'hierarchical' => '<i class="drts-icon drts-icon-sm fa-fw fas fa-' . $icon . '" title=""></i>',
                'links' => empty($active_custom_taxonomy_bundles[$custom_taxonomy_name]) ?
                    $delete_link :
                    sprintf(
                        $delete_link_disabled,
                        implode(', ', array_keys($active_custom_taxonomy_bundles[$custom_taxonomy_name]))
                    ),
            ];
        }

        return [
            0 => [
                '#type' => 'grid',
                '#size' => 'sm',
                '#class' => 'drts-data-table',
                '#id' => '__FORM_ID__-custom-taxonomies',
                '#js_ready' => 'DRTS.DirectoryPro.adminCustomTaxonomies("#__FORM_ID__-custom-taxonomies");',
                '#element_validate' => [function(Form $form, &$value, $element) use ($application) {
                    $config = $application->getComponent('DirectoryPro')->getConfig();
                    $custom_taxonomies = empty($config['custom_taxonomies']) ? [] : $config['custom_taxonomies'];
                    $new_custom_taxonomies = [];
                    foreach (array_keys($value) as $taxonomy_name) {
                        if (!isset($custom_taxonomies[$taxonomy_name])
                            || !isset($_POST['DirectoryPro']['custom_taxonomies'][0][$taxonomy_name]) // was removed
                        ) continue;

                        foreach (['label', 'label_singular', 'icon'] as $key) {
                            if (empty($value[$taxonomy_name][$key])
                                || !strlen($value[$taxonomy_name][$key] = trim($value[$taxonomy_name][$key]))
                            ) {
                                $form->setError(sprintf('Label and icon for custom taxonomy (%s) may not be empty.', $taxonomy_name), $element);
                                return;
                            }
                        }
                        $new_custom_taxonomies[$taxonomy_name] = $custom_taxonomies[$taxonomy_name];
                        $new_custom_taxonomies[$taxonomy_name]['label'] = $value[$taxonomy_name]['label'];
                        $new_custom_taxonomies[$taxonomy_name]['label_singular'] = $value[$taxonomy_name]['label_singular'];
                        $new_custom_taxonomies[$taxonomy_name]['icon'] = $value[$taxonomy_name]['icon'];
                        unset($custom_taxonomies[$taxonomy_name]);
                    }
                    ksort($new_custom_taxonomies);
                    $config['custom_taxonomies'] = $new_custom_taxonomies;
                    $application->System_Component_saveConfig('DirectoryPro', $config, false);
                    $application->clearComponentInfoCache();
                }],
                'hierarchical' => [
                    '#type' => 'markup',
                    '#title' => '',
                ],
                'name' => [
                    '#type' => 'markup',
                    '#title' => _x('Name', 'taxonomy name', 'directories-pro'),
                ],
                'label' => [
                    '#type' => 'textfield',
                    '#title' => __('Label', 'directories-pro'),
                    '#placeholder' => __('(e.g. Features)', 'directories-pro'),
                ],
                'label_singular' => [
                    '#type' => 'textfield',
                    '#title' => __('Singular label', 'directories-pro'),
                    '#placeholder' => __('(e.g. Feature)', 'directories-pro'),
                ],
                'icon' => [
                    '#type' => 'iconpicker',
                    '#title' => '',
                    '#title' => __('Icon', 'directories-pro'),
                ],
                'links' => [
                    '#type' => 'markup',
                    '#title' => '',
                ],
                '#row_settings' => [
                    '_' => [
                        'label' => [
                            '#attributes' => [
                                'disabled' => 'disabled',
                             ],
                        ],
                        'label_singular' => [
                            '#attributes' => [
                                'disabled' => 'disabled',
                            ],
                        ],
                        'icon' => [
                            '#attributes' => [
                                'disabled' => 'disabled',
                            ],
                        ],
                    ],
                ],
                '#column_attributes' => [
                    'icon' => ['style' => 'width:15%;'],
                    'label_singular' => ['style' => 'width:25%;'],
                    'label' => ['style' => 'width:25%;'],
                    'name' => ['style' => 'width:20%;'],
                    'hierarchical' => ['style' => 'width:5%;'],
                    'links' => ['style' => 'width:5%;'],
                ],
                '#row_attributes' => [
                    '@all' => [
                        'hierarchical' => [
                            'style' => 'text-align:center;',
                        ],
                        'links' => [
                            'style' => 'text-align:' . $application->getPlatform()->isRtl() ? 'left;' : 'right;',
                        ],
                    ],
                    '_' => [
                        '@row' => [
                            'style' => 'display:none', // hide dummy template row
                        ]
                    ],
                ],
                '#default_value' => $custom_taxonomies,
                '#default_markup' => $custom_taxonomies,
            ],
            '_add' => [
                '#type' => 'markup',
                '#markup' => $application->LinkTo(
                    '<i class="fas fa-plus fa-fw"></i> ' . $application->H(__('Add Custom Taxonomy', 'directories-pro')),
                    $application->Url('/_drts/directorypro/add_custom_taxonomy', ['field_id' => '__FORM_ID__-custom-taxonomies']),
                    ['container' => 'modal', 'modalSize' => 'xl', 'no_escape' => true],
                    ['class' => DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-outline-secondary ' . DRTS_BS_PREFIX . 'mt-2']
                ),
            ],
        ];
    }

    public function add(Application $application, $name, $label, $labelSingular, $icon, $hierarchical)
    {
        $config = (array)$application->getComponent('DirectoryPro')->getConfig();
        if (!isset($config['custom_taxonomies'])) {
            $config['custom_taxonomies'] = [];
        }
        $values = [
            'label' => $label,
            'label_singular' => $labelSingular,
            'icon' => $icon,
            'hierarchical' => empty($hierarchical) ? 0 : 1,
        ];
        $config['custom_taxonomies'][$name] = $values;
        $application->System_Component_saveConfig('DirectoryPro', $config, false);
        $application->clearComponentInfoCache();

        return ['name' => $name] + $values;
    }
}
