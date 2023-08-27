<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class PermissionsHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle)
    {
        $permissions = [];
        if ($application->Entity_BundleTypeInfo($bundle, 'entity_permissions') === false) {
            return $permissions;
        }

        $label = $bundle->getLabel();
        switch ($bundle->entitytype_name) {
            case 'term':
                $permissions += array(
                    'entity_manage' => array(
                        'title' => sprintf(_x('Manage %s', 'permission', 'directories'), $label),
                        'weight' => 1,
                    ),
                    'entity_edit' => array(
                        'title' => sprintf(_x('Edit %s', 'permission', 'directories'), $label),
                        'weight' => 5,
                    ),
                    'entity_delete' => array(
                        'title' => sprintf(_x('Delete %s', 'permission', 'directories'), $label),
                        'weight' => 10,
                    ),
                    'entity_assign' => array(
                        'title' => sprintf(_x('Assign %s', 'permission', 'directories'), $label),
                        'default' => true,
                        'weight' => 15,
                    ),
                );
                break;
            case 'user':
                $permissions += array(
                    'entity_read' => array(
                        'title' => sprintf(_x('Read %s', 'permission', 'directories'), $label),
                        'guest_allowed' => true,
                        'default' => true,
                        'weight' => 1,
                    ),
                );
                break;
            default:
                if (!empty($bundle->info['public'])) {
                    $permissions += array(
                        'entity_read' => array(
                            'title' => sprintf(_x('Read %s', 'permission', 'directories'), $label),
                            'guest_allowed' => true,
                            'default' => true,
                            'weight' => 1,
                        ),
                        'entity_create' => array(
                            'title' => sprintf(_x('Create %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 10,
                        ),
                        'entity_publish' => array(
                            'title' => sprintf(_x('Publish %s', 'permission', 'directories'), $label),
                            'weight' => 15,
                        ),
                        'entity_edit' => array(
                            'title' => sprintf(_x('Edit own %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 20,
                        ),
                        'entity_edit_others' => array(
                            'title' => sprintf(_x('Edit others %s', 'permission', 'directories'), $label),
                            'weight' => 25,
                        ),
                        'entity_edit_published' => array(
                            'title' => sprintf(_x('Edit published %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 30,
                        ),
                        'entity_delete' => array(
                            'title' => sprintf(_x('Delete own %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 40,
                        ),
                        'entity_delete_others' => array(
                            'title' => sprintf(_x('Delete others %s', 'permission', 'directories'), $label),
                            'weight' => 45,
                        ),
                        'entity_delete_published' => array(
                            'title' => sprintf(_x('Delete published %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 50,
                        ),
                        'entity_moderate_comments' => array(
                            'title' => _x('Moderate comments', 'permission', 'directories'),
                            'default' => true,
                            'weight' => 55,
                        ),
                    );
                    if (!empty($bundle->info['privatable'])) {
                        $permissions += array(
                            'entity_read_private' => array(
                                'title' => sprintf(_x('Read others private %s', 'permission', 'directories'), $label),
                                'weight' => 5,
                            ),
                            'entity_edit_private' => array(
                                'title' => sprintf(_x('Edit others private %s', 'permission', 'directories'), $label),
                                'default' => true,
                                'weight' => 35,
                            ),
                            'entity_delete_private' => array(
                                'title' => sprintf(_x('Delete others private %s', 'permission', 'directories'), $label),
                                'default' => true,
                                'weight' => 55,
                            ),
                        );
                    }
                } else {
                    $permissions += array(
                        'entity_create' => array(
                            'title' => sprintf(_x('Create %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 1,
                        ),
                        'entity_edit' => array(
                            'title' => sprintf(_x('Edit own %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 5,
                        ),
                        'entity_edit_others' => array(
                            'title' => sprintf(_x('Edit others %s', 'permission', 'directories'), $label),
                            'weight' => 10,
                        ),
                        'entity_delete' => array(
                            'title' => sprintf(_x('Delete own %s', 'permission', 'directories'), $label),
                            'default' => true,
                            'weight' => 15,
                        ),
                        'entity_delete_others' => array(
                            'title' => sprintf(_x('Delete others %s', 'permission', 'directories'), $label),
                            'weight' => 20,
                        ),
                    );
                }
        }
        
        $permissions = $application->Filter('entity_permissions', $permissions, array($bundle));
        uasort($permissions, function($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1;});
        
        return $permissions;
    }
}