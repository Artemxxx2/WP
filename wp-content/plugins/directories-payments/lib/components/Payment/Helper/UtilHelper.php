<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Exception;

class UtilHelper
{
    public function actionLabel(Application $application, $action, $wasDeactivated = false)
    {
        switch ($action) {
            case 'upgrade':
                return __('Upgrade / Downgrade', 'directories-payments');
            case 'renew':
                return __('Renewal', 'directories-payments');
            case 'order_addon':
                return __('Order Add-on', 'directories-payments');
            case 'resubscribe':
                return __('Resubscribe', 'directories-payments');
            case 'claim':
                return __('Claim', 'directories-payments');
            default:
                return $wasDeactivated ? __('Re-activation', 'directories-payments') : __('Initial Post', 'directories-payments');
        }
    }
    
    public function hasPendingOrder(Application $application, IEntity $entity, array $actions = null)
    {
        if (!$payment_component = $application->getComponent('Payment')->getPaymentComponent()) return false;
        
        return $payment_component->paymentHasPendingOrder($entity, isset($actions) ? $actions: ['add', 'submit', 'renew', 'upgrade', 'order_addon']);
    }
    
    public function handleExpired(Application $application, $entityType, array $entities, array &$logs = null)
    {
        if (empty($entities)) return;

        $action = $application->getComponent('Payment')->getConfig('expired', 'action');
        switch ($action) {
            case 'trash':
                $this->trashEntities($application, $entityType, $entities, $logs);
                break;
            case 'no_plan':
            case 'no_plan_author':
                $values = ['payment_plan' => false];
                if ($action === 'no_plan') {
                    $msg = 'Unassigned payment plan from item: %s (ID: %d)';
                } else {
                    $values['author'] = false;
                    $msg = 'Unassigned payment plan and author from item: %s (ID: %d)';
                }
                foreach (array_keys($entities) as $entity_id) {
                    try {
                        $application->Payment_Features_unapply($entities[$entity_id], null, $values);
                    } catch (Exception\IException $e) {
                        if (isset($logs)) {
                            $logs['error'][] = $e->getMessage();
                        } else {
                            $application->logError($e);
                        }

                        continue;
                    }
                    if (isset($logs)) {
                        $logs['success'][] = sprintf($msg, $entities[$entity_id]->getTitle(), $entity_id);
                    }
                }
                break;
            case 'none':
                break;
            default:
                if (defined('DRTS_PAYMENT_NO_DEACTIVATION') && DRTS_PAYMENT_NO_DEACTIVATION) break;

                $this->deactivateEntities($application, $entityType, $entities, $logs);
        }
    }

    public function reactivateEntities(Application $application, $entityType, array $entities, array &$logs = null)
    {
        if (empty($entities)) return;

        $values = [
            'status' => $application->Entity_Status($entityType, 'publish'),
            'payment_plan' => ['deactivated_at' => 0],
        ];
        $this->_updateEntities($application, $entities, $values, 'Reactivated item: %s (ID: %d)', $logs);
    }

    public function deactivateEntities(Application $application, $entityType, array $entities, array &$logs = null)
    {
        if (empty($entities)) return;

        $values = [
            'payment_plan' => ['deactivated_at' => time()],
        ];
        if ($status = $application->Filter('payment_entities_deactivated_status', 'draft', [$entityType, $entities])) {
            $values['status'] = $application->Entity_Status($entityType, $status);
        }
        $this->_updateEntities($application, $entities, $values, 'Deactivated item: %s (ID: %d)', $logs);
        $first_entity_key = array_keys($entities)[0];
        $application->Action('payment_entities_deactivated', [$entities[$first_entity_key]->getBundleName(), $entities, &$logs]);
    }

    protected function _updateEntities(Application $application, array $entities, array $values, $successMsg, array &$logs = null)
    {
        foreach (array_keys($entities) as $entity_id) {
            try {
                $application->Entity_Save($entities[$entity_id], $values);
            } catch (Exception\IException $e) {
                if (isset($logs)) {
                    $logs['error'][] = $e->getMessage();
                } else {
                    $application->logError($e);
                }
                continue;
            }

            // Deactivate translated entities
            foreach ($application->Entity_Translations($entities[$entity_id], false) as $translated_entity) {
                try {
                    $application->Entity_Save($translated_entity, $values);
                } catch (Exception\IException $e) {
                    if (isset($logs)) {
                        $logs['error'][] = $e->getMessage();
                    } else {
                        $application->logError($e);
                    }
                    continue;
                }
            }

            if (isset($logs)) {
                $logs['success'][] = sprintf($successMsg, $entities[$entity_id]->getTitle(), $entity_id);
            }
        }
    }

    public function trashEntities(Application $application, $entityType, array $entities, array &$logs = null)
    {
        if (empty($entities)) return;

        $values = [
            'status' => $application->Entity_Status($entityType, 'trash'),
        ];
        foreach (array_keys($entities) as $entity_id) {
            try {
                $application->Payment_Features_unapply($entities[$entity_id], null, $values);
            } catch (Exception\IException $e) {
                if (isset($logs)) {
                    $logs['error'][] = $e->getMessage();
                } else {
                    $application->logError($e);
                }
                continue;
            }
            if (isset($logs)) {
                $logs['success'][] = sprintf('Trashed item: %s (ID: %d)', $entities[$entity_id]->getTitle(), $entity_id);
            }
        }
    }
}
