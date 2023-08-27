<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\AbstractIdentity;
use SabaiApps\Directories\Component\Display\Controller\Admin\AddDisplay;

class PostsPanel extends AbstractPanel
{
    protected $_bundleGroup;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_bundleGroup = substr($name, strlen('posts_'));
    }

    protected function _dashboardPanelInfo()
    {
        return [
            'weight' => 1,
            'labellable' => false,
            'default_settings' => [
                'add_post_btn' => true,
                'add_show_label' => false,
                'show_others' => false,
            ],
        ];
    }

    public function dashboardPanelOnLoad($isPublic = false)
    {
        if ($this->_application->getPlatform()->isAdmin()) return;

        $this->_application->getPlatform()->loadJqueryUiJs(array('effects-highlight'));
    }

    public function dashboardPanelLabel()
    {
        if ($directory = $this->_application->getModel('Directory', 'Directory')->name_is($this->_bundleGroup)->fetchOne()) {
            return $directory->getLabel();
        }
    }

    protected function _dashboardPanelLinks(array $settings, AbstractIdentity $identity = null)
    {
        if (!$bundles = $this->_application->Entity_Bundles(null, 'Directory', $this->_bundleGroup)) return;

        $ret = [];
        $weight = 0;
        $entity_type = null;
        foreach ($bundles as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])
                || (isset($identity) && empty($bundle->info['public']))
            ) continue;

            ++$weight;
            $ret[$bundle->name] = array(
                'title' => $bundle->getLabel(),
                'weight' => empty($bundle->info['parent']) ? $weight : 100 + $weight,
                'icon' => $this->_application->Entity_BundleTypeInfo($bundle, 'icon'),
            );
            $entity_type = $bundle->entitytype_name;
        }

        $user_id = isset($identity) ? $identity->id : $this->_application->getUser()->id;
        $language = $this->_application->getPlatform()->getCurrentLanguage();
        $cache_id = 'dashboard_post_counts_' . $this->_bundleGroup . '_' . $user_id . '_' . $language;
        $show_others = false;
        if (!isset($identity)
            && ($show_others = !empty($settings['show_others']))
        ) {
            $cache_id .= '_o' . (int)$show_others;
        }
        if (!$counts = $this->_application->getPlatform()->getCache($cache_id, 'content')) {
            $counts = [];
            if (!empty($ret)) {
                $statuses = [];
                $valid_status_keys = isset($identity) ? ['publish'] : ['publish', 'pending', 'draft', 'private'];
                foreach ($valid_status_keys as $status_key) {
                    $statuses[$status_key] = $this->_application->Entity_Status($entity_type, $status_key);
                }

                foreach (array_keys($ret) as $bundle_name) {
                    if ($show_others
                        && ($this->_application->HasPermission('entity_edit_others_' . $bundle_name)
                            || $this->_application->HasPermission('entity_delete_others_' . $bundle_name))
                    ) {
                        $other_statuses = $statuses;
                        if (!$this->_application->HasPermission('entity_edit_published_' . $bundle_name)
                            && !$this->_application->HasPermission('entity_delete_published_' . $bundle_name)
                        ) {
                            unset($other_statuses['publish']);
                        }
                        if (!$this->_application->HasPermission('entity_edit_private_' . $bundle_name)
                            && !$this->_application->HasPermission('entity_delete_private_' . $bundle_name)
                        ) {
                            unset($other_statuses['private']);
                        }
                        $counts[$bundle_name] = $this->_application->Entity_Query($entity_type, $bundle_name)
                            ->startCriteriaGroup('OR')
                                ->startCriteriaGroup('AND')
                                    ->fieldIsIn('status', $statuses)
                                    ->fieldIs('author', $user_id)
                                ->finishCriteriaGroup()
                                ->startCriteriaGroup('AND')
                                    ->fieldIsIn('status', $other_statuses)
                                    ->fieldIsNot('author', $user_id)
                                ->finishCriteriaGroup()
                            ->finishCriteriaGroup()
                            ->count();
                    } else {
                        $counts[$bundle_name] = $this->_application->Entity_Query($entity_type, $bundle_name)
                            ->fieldIsIn('status', $statuses)
                            ->fieldIs('author', $user_id)
                            ->count();
                    }
                }
            }
            $this->_application->getPlatform()->setCache($counts, $cache_id, 3600, 'content'); // cache 1 hour
        }
        foreach (array_keys($ret) as $bundle_name) {
            if (empty($counts[$bundle_name])) {
                // No posts, hide if child bundle, public profile, or no permission to create posts for the bundle
                if (!empty($bundles[$bundle_name]->info['parent'])
                    || isset($identity)
                    || !$this->_application->HasPermission('entity_create_' . $bundle_name)
                ) {
                    unset($ret[$bundle_name]);
                }
            } else {
                $ret[$bundle_name]['count'] = $counts[$bundle_name];
            }
        }

        return $ret;
    }

    public function dashboardPanelContent($link, array $settings, array $params, AbstractIdentity $identity = null)
    {
        if (!$bundle = $this->_application->Entity_Bundle($link)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $link);
        }

        if (isset($identity)) {
            if (empty($bundle->info['public'])) return; // this should not happen but just in case
        }

        $_settings = [
            'filter' => [
                'show' => !empty($settings['filter_show_' . $bundle->name]),
                'show_modal' => !empty($settings['filter_show_modal_' . $bundle->name]),
                'display' => empty($settings['filter_display_' . $bundle->name]) ? null : $settings['filter_display_' . $bundle->name],
            ],
            'pagination' => [
                'perpage' => 20,
                'allow_perpage' => true,
                'perpages' => [20, 30, 50],
            ],
            'other' => [
                'add' => [
                    'show' => empty($bundle->info['parent']) && (!isset($settings['add_post_btn']) || $settings['add_post_btn']),
                    'show_label' => !empty($settings['add_show_label']),
                ],
                'num' => true,
            ],
            'query' => [
                'user_id' => isset($identity) ? $identity->id : null,
            ],
        ];
        $sorts = isset($settings['sorts_' . $bundle->name]) ? $settings['sorts_' . $bundle->name] : $this->_getDefaultSortOptions($bundle);
        if (!empty($sorts)) {
            $_settings['sort'] = [
                'default' => current(array_keys($sorts)),
                'options' => $sorts,
            ];
        }
        if (!empty($bundle->info['public'])) {
            if (!isset($identity)) {
                $_settings['query']['status'] = ['publish', 'pending', 'draft', 'private'];

                if (!empty($settings['show_others'])) {
                    // Set statuses for other user posts if permitted
                    if ($this->_application->HasPermission('entity_edit_others_' . $bundle->name)
                        || $this->_application->HasPermission('entity_delete_others_' . $bundle->name)
                    ) {
                        $status_others = ['pending', 'draft'];
                        if ($this->_application->HasPermission('entity_edit_published_' . $bundle->name)
                            || $this->_application->HasPermission('entity_delete_published_' . $bundle->name)
                        ) {
                            $status_others[] = 'publish';
                        }
                        if ($this->_application->HasPermission('entity_edit_private_' . $bundle->name)
                            || $this->_application->HasPermission('entity_delete_private_' . $bundle->name)
                        ) {
                            $status_others[] = 'private';
                        }
                        $_settings['query']['status_others'] = $status_others;
                    }
                }
            }
        }

        return $this->_application->getPlatform()->render(
            $bundle->getPath(),
            ['settings' => ['mode' => 'dashboard_dashboard', 'settings' => $this->_application->Filter('dashboard_posts_panel_view_settings', $_settings, [$bundle, $identity])]]
        );
    }

    protected function _getDefaultSortOptions(Bundle $bundle)
    {
        if (empty($bundle->info['public'])) {
            $sorts = [
                $bundle->entitytype_name . '_published',
                $bundle->entitytype_name . '_published,asc',
            ];
        } else {
            $sorts = [
                $bundle->entitytype_name . '_published',
                $bundle->entitytype_name . '_title',
                $bundle->entitytype_name . '_published,asc',
                'entity_level',
                empty($bundle->info['review_enable']) ? 'voting_rating' : 'review_ratings',
                'voting_bookmark',
                'voting_updown',
            ];
        }
        return $sorts;
    }

    public function dashboardPanelSettingsForm(array $settings, array $parents)
    {
        $form = [];
        $primary_bundle = null;
        if ($bundles = $this->_application->Entity_Bundles(null, 'Directory', $this->_bundleGroup)) {
            $weight = 10;
            foreach ($bundles as $bundle) {
                if (!empty($bundle->info['is_primary'])) {
                    $primary_bundle = $bundle;
                }

                if (!$this->_application->getComponent('View')->isFilterable($bundle)
                    || (!$displays = AddDisplay::existingDisplays($this->_application, $bundle->name,'default', 'filters'))
                ) continue;

                $title_prefix = empty($bundle->info['is_primary']) ? $bundle->getLabel() . ' - '  : '';
                ++$weight;
                $show_key = 'filter_show_' . $bundle->name;
                $show_selector = sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, [$show_key])));
                $form[$show_key] = [
                    '#type' => 'checkbox',
                    '#title' => $title_prefix . __('Show filter form', 'directories-frontend'),
                    '#horizontal' => true,
                    '#default_value' => !empty($settings[$show_key]),
                    '#weight' => $weight,
                ];
                $show_modal_key = 'filter_show_modal_' . $bundle->name;
                $form[$show_modal_key] = [
                    '#type' => 'checkbox',
                    '#title' => $title_prefix . __('Show filter form in modal window', 'directories-frontend'),
                    '#default_value' => !empty($settings[$show_modal_key]),
                    '#horizontal' => true,
                    '#weight' => $weight,
                    '#states' => [
                        'visible' => [
                            $show_selector => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ];
                $display_key = 'filter_display_' . $bundle->name;
                if (count($displays) > 1) {
                    $form[$display_key] = [
                        '#type' => 'select',
                        '#title' => $title_prefix . __('Select filter group', 'directories-frontend'),
                        '#options' => $displays,
                        '#horizontal' => true,
                        '#default_value' => isset($settings[$display_key]) && isset($displays[$settings[$display_key]]) ? $settings[$display_key] : null,
                        '#weight' => $weight,
                        '#states' => [
                            'visible' => [
                                $show_selector  => ['type' => 'checked', 'value' => true],
                            ],
                        ],
                    ];
                } else {
                    $form[$display_key] = [
                        '#type' => 'hidden',
                        '#default_value' => '',
                    ];
                }
                $sorts_key = 'sorts_' . $bundle->name;
                $form[$sorts_key] = [
                    '#type' => 'sortablecheckboxes',
                    '#options' => $sorts = $this->_application->Entity_Sorts_options($bundle, true),
                    '#option_no_escape' => true,
                    '#default_value' => isset($settings[$sorts_key]) ? $settings[$sorts_key] : $this->_getDefaultSortOptions($bundle),
                    '#title' => $title_prefix . __('Sort options', 'directories-frontend'),
                    '#horizontal' => true,
                    '#weight' => $weight,
                ];
            }
        }
        $form += [
            'add_post_btn' => [
                '#type' => 'checkbox',
                '#title' => sprintf(__('Show "%s" button', 'directories-frontend'), $primary_bundle->getLabel('add')),
                '#horizontal' => true,
                '#default_value' => !isset($settings['add_post_btn']) || !empty($settings['add_post_btn']),
                '#weight' => 2,
            ],
            'add_show_label' => [
                '#type' => 'checkbox',
                '#title' => sprintf(__('Show "%s" button with label', 'directories-frontend'), $primary_bundle->getLabel('add')),
                '#default_value' => !empty($settings['add_show_label']),
                '#horizontal' => true,
                '#weight' => 3,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['add_post_btn']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'show_others' => [
                '#type' => 'checkbox',
                '#title' => __("Include other user's posts", 'directories-frontend'),
                '#horizontal' => true,
                '#default_value' => !empty($settings['show_others']),
                '#weight' => 5,
            ],
        ];

        return $form;
    }
}
