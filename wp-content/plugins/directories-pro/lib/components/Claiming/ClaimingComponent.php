<?php
namespace SabaiApps\Directories\Component\Claiming;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\IBundleTypes;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class ClaimingComponent extends AbstractComponent implements
    IBundleTypes,
    Field\ITypes,
    Field\IWidgets,
    Field\IFilters,
    Display\ILabels,
    Display\IButtons
{
    const VERSION = '1.3.108', PACKAGE = 'directories-pro';
    
    public static function interfaces()
    {
        return ['WordPressContent\INotifications'];
    }
    
    public static function description()
    {
        return 'Allows listing owners to claim their listings and get verified.';
    }

    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Claiming_Statuses', [__CLASS__, 'statusesHelper']);
    }

    public static function statusesHelper(Application $application)
    {
        return $application->Filter('claiming_statuses', [
            'approved' => ['label' => __('Approved', 'directories-pro'), 'color' => 'success'],
            'rejected' => ['label' => __('Rejected', 'directories-pro'), 'color' => 'danger'],
        ]);
    }

    public function fieldGetTypeNames()
    {
        return array('claiming_status');
    }
    
    public function fieldGetType($name)
    {
        return new FieldType\StatusFieldType($this->_application, $name);
    }
    
    public function fieldGetWidgetNames()
    {
        return array('claiming_status');
    }
    
    public function fieldGetWidget($name)
    {
        return new FieldWidget\StatusFieldWidget($this->_application, $name);
    }

    public function fieldGetFilterNames()
    {
        return ['claiming_claimed'];
    }

    public function fieldGetFilter($name)
    {
        return new FieldFilter\ClaimedFieldFilter($this->_application, $name);
    }

    public function displayGetLabelNames(Bundle $bundle)
    {
        $ret = [];
        if ($bundle->type === 'claiming_claim') {
            $ret[] = 'claiming_status';
        } elseif (!empty($bundle->info['claiming_enable'])) {
            $ret[] = 'claiming_claimed';
        }
        return $ret;
    }
    
    public function displayGetLabel($name)
    {
        switch ($name) {
            case 'claiming_status':
                return new DisplayLabel\StatusDisplayLabel($this->_application, $name);
            case 'claiming_claimed':
                return new DisplayLabel\ClaimedEntityDisplayLabel($this->_application, $name);
        }
    }
    
    public function displayGetButtonNames(Bundle $bundle)
    {
        return empty($bundle->info['claiming_enable']) ? [] : ['claiming_claim'];
    }
    
    public function displayGetButton($name)
    {
        return new DisplayButton\ClaimEntityDisplayButton($this->_application, $name);
    }
    
    public function entityGetBundleTypeNames()
    {        
        return array('claiming_claim');
    }
    
    public function entityGetBundleType($name)
    {
        return new EntityBundleType\ClaimEntityBundleType($this->_application, $name);
    }
    
    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (!isset($info['claiming_enable'])
            || !empty($info['parent'])
            || !empty($info['is_taxonomy'])
        ) return;
        
        $form['claiming_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable claims', 'directories-pro'),
            '#default_value' => !empty($settings['claiming_enable']) || is_null($settings),
            '#horizontal' => true,
            '#weight' => 40,
        );
    }
    
    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {        
        if (!isset($info['claiming_enable'])) return;
        
        if (!empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) {
            unset($info['claiming_enable']);
        }
        
        if (isset($settings['claiming_enable']) && !$settings['claiming_enable']) {
            $info['claiming_enable'] = false;
        }
    }
    
    public function onEntityBundlesInfoFilter(&$bundles, $componentName, $group)
    {
        foreach (array_keys($bundles) as $bundle_type) {
            $info =& $bundles[$bundle_type];
            
            if (empty($info['claiming_enable'])
                || !empty($info['is_taxonomy'])
                || !empty($info['parent'])
            ) continue;

            // Add claim bundle
            if (!isset($bundles['claiming_claim'])) { // may already set if updating or importing
                $bundles['claiming_claim'] = [];
            }
            $bundles['claiming_claim']['parent'] = $bundle_type; // must be bundle type for Entity component to create parent field
            $bundles['claiming_claim'] += $this->entityGetBundleType('claiming_claim')->entityBundleTypeInfo();
            $bundles['claiming_claim']['properties']['parent']['label'] = $info['label_singular'];
            
            return; // there should be only one bundle with claiming enabled in a group
        }
        
        // No bundle with claiming enabled found, so make sure the claiming_claim bundle is not assigned
        unset($bundles['claiming_claim']);
    }
    
    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'claiming_enable';
    }
    
    public function onEntityCreateEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        if ($bundle->type !== 'claiming_claim'
            || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        if (in_array($status = $this->getClaimStatus($entity), ['approved', 'rejected'])) {
            $this->_onClaimApprovedOrRejected($status, $entity, $parent_entity);
        } else {
            $this->_application->Action('claiming_claim_pending', [$entity, $parent_entity]);
        }
    }
    
    public function onEntityUpdateEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        if ($bundle->type !== 'claiming_claim'
            || empty($values['claiming_status'])
            || !empty($extraArgs['claiming_skip_update_success_callback'])
            || in_array($oldEntity->getSingleFieldValue('claiming_status'), ['approved', 'rejected']) // make sure has not previously been approved/rejected
            || !in_array($status = $this->getClaimStatus($entity), ['approved', 'rejected']) // make sure has been approved/rejected with this update
            || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        $this->_onClaimApprovedOrRejected($status, $entity, $parent_entity);
    }
    
    protected function _onClaimApprovedOrRejected($status, IEntity $claim, IEntity $claimedEntity)
    {
        if ($status === 'approved') {
            // Update author of the parent entity to that of the claim
            $this->_application->Entity_Save($claimedEntity, ['author' => $claim->getAuthorId()]);
            
            // Claim translated posts
            foreach ($this->_application->Entity_Translations($claimedEntity, false) as $entity) {
                if ($entity->getAuthorId() !== $claim->getAuthorId()) {
                    $this->_application->Entity_Save($entity, ['author' => $claim->getAuthorId()]);
                }
            }                
            $this->_application->Action('claiming_claim_approved', [$claim, $claimedEntity]);
        } elseif ($status === 'rejected') {
            $this->_application->Action('claiming_claim_rejected', [$claim, $claimedEntity]);
        }
    }
    
    public function onEntityIsClaimingClaimRoutableFilter(&$result, $bundle, $action, IEntity $entity = null)
    {
        if ($result === false
            || !in_array($action, ['add', 'edit'])
        ) return;

        if (!isset($entity)) {
            throw new Exception\InvalidArgumentException('Missing context entity');
        }
        
        if ($action === 'add') {
            // Do not allow claiming if target entity already has an author
            if ($entity->getAuthorId()) {
                $result = false;
            }
            // Allow other components to filter result
            $result = $this->_application->Filter('claiming_is_entity_claimable', $result, [$entity]);
        } elseif ($action === 'edit') {
            // Do not allow editing of already approved/rejected claims
            if (in_array($this->getClaimStatus($entity), ['approved', 'rejected'])) {
                $result = false;
            }
        }
    }

    public function onEntityPermissionsFilter(&$permissions, Bundle $bundle)
    {
        if ($bundle->type !== 'claiming_claim') return;

        $permissions['entity_create']['guest_allowed'] = $this->_application->Filter('claiming_is_guest_claimable', false, [$bundle]);
    }
    
    public function wpGetNotificationNames()
    {
        return array(
            'claiming_pending',
            'claiming_approved',
            'claiming_rejected',
        );
    }
    
    public function wpGetNotification($name)
    {
        return new WordPressNotification\ClaimWordPressNotification($this->_application, $name);
    }

    public function onClaimingClaimPending($claim, IEntity $claimedEntity)
    {
        if ($this->_application->isComponentLoaded('WordPress')) {
            $this->_application->WordPressContent_Notifications_send('claiming_pending', $claim, $claimedEntity);
        }
    }

    public function onClaimingClaimApproved($claim, IEntity $claimedEntity)
    {
        if ($this->_application->isComponentLoaded('WordPress')) {
            $this->_application->WordPressContent_Notifications_send('claiming_approved', $claim, $claimedEntity);
        }
    }

    public function onClaimingClaimRejected($claim, IEntity $claimedEntity)
    {
        if ($this->_application->isComponentLoaded('WordPress')) {
            $this->_application->WordPressContent_Notifications_send('claiming_rejected', $claim, $claimedEntity);
        }
    }

    public function onFrontendsubmitGuestAllowedFilter(&$allowed, $bundleOrBundleType, $action)
    {
        if ($bundleOrBundleType instanceof Bundle
            && $bundleOrBundleType->type === 'claiming_claim'
            && $action === 'add'
        ) {
            $allowed = $this->_application->Filter('claiming_is_guest_claimable', false, [$bundleOrBundleType]);
        }
    }

    public function getClaimStatus(IEntity $entity)
    {
        if ($entity->getBundleType() !== 'claiming_claim') throw new Exception\InvalidArgumentException('Invalid claim entity.');

        if (!$entity->isPublished()) return;

        $status = $entity->getSingleFieldValue('claiming_status');
        if (in_array($status, ['approved', 'rejected'])) return $status;

        // Allow changing claim status
        if (null !== $status = $this->_application->Filter('claiming_claim_status', null, [$entity])) {
            $status = $status === false || $status === 'rejected' ? 'rejected' : 'approved';
            $this->_application->Entity_Save(
                $entity,
                ['claiming_status' => $status],
                ['claiming_skip_update_success_callback' => true] // prevents loop
            );
        }

        return $status;
    }
}