<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;

class PersonalDataHelper
{
    public function exporters(Application $application)
    {
        $exporters = [];
        foreach ($application->Entity_PersonalData_fields() as $bundle_name => $fields) {
            if (!$bundle = $application->Entity_Bundle($bundle_name)) continue;

            $exporters['post-' . $bundle_name] = [
			    'exporter_friendly_name' => $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
			    'callback' => function ($email, $page) use ($application, $bundle_name, $label, $fields)  {
                    $ret = ['data' => [], 'done' => true];
                    if (($user = get_user_by('email', $email))
                        && $user->ID
                    ) {
                        if ($personal_data = $application->Entity_PersonalData($bundle_name, $fields, $email, $user->ID)) {
                            foreach (array_keys($personal_data) as $entity_id) {
                                $ret['data'][] = [
							        'item_id' => 'post-' . $bundle_name . '-' . $entity_id,
							        'group_id' => 'post-' . $bundle_name,
							        'group_label' => $label,
							        'data' => $personal_data[$entity_id] + [
                                        'permalink' => [
                                            'name' => __('Permalink URL', 'directories'),
                                            'value' => get_permalink($entity_id),
                                        ],
                                    ],
						        ];
                            }
                        }
                    }
                    return $ret;
                },
		    ];
        }

        return $exporters;
    }

    public function erasers(Application $application)
    {
        $erasers = [];
        foreach ($application->Entity_PersonalData_fields() as $bundle_name => $fields) {
            if (!$bundle = $application->Entity_Bundle($bundle_name)) continue;

            $erasers['post-' . $bundle_name] = [
			    'eraser_friendly_name' => $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
			    'callback' => function ($email, $page) use ($application, $bundle_name, $fields)  {
                    $ret = ['items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true];
                    if (($user = get_user_by('email', $email))
                        && $user->ID
                    ) {
                        $results = $application->Entity_PersonalData_erase($bundle_name, $fields, $email, $user->ID);
                        if (!empty($results['deleted'])) $ret['items_removed'] = $results['deleted'];
                        if (!empty($results['retained'])) $ret['items_retained'] = $results['retained'];
                        if (!empty($results['messages'])) $ret['messages'] = $results['messages'];
                    }
                    return $ret;
                },
		    ];
        }

        return $erasers;
    }
}
