<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Payment\ISubscription;
use SabaiApps\Framework\Paginator\CustomPaginator;

class DashboardSubscriptions extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        $payment_component = $this->getComponent('Payment')->getPaymentComponent(true);

        if (($subscription_id = $context->getRequest()->asStr('subscription_id'))
            && ($item_id = $context->getRequest()->asStr('item_id'))
            && ($subscription = $payment_component->paymentGetSubscription($subscription_id, $item_id))
        ) {
            // Show single user subscription
            return $this->_viewSingleSubscription($context, $subscription);
        }

        // Show all user subscriptions

        // Init form
        $form = [
            'subscriptions' => [
                '#type' => 'tableselect',
                '#header' => [
                    'id' => __('ID', 'directories-payments'),
                    'name' => __('Name', 'directories-payments'),
                    'next' => __('Next Payment', 'directories-payments'),
                    'total' => __('Total', 'directories-payments'),
                    'status' => __('Status', 'directories-payments'),
                ],
                '#options' => [],
                '#options_disabled' => [],
                '#disabled' => true,
                '#multiple' => true,
                '#class' => 'drts-data-table',
                '#row_attributes' => [
                    '@all' => [
                        'status' => ['class' => 'drts-paidlistings-subscriptions-status'],
                    ],
                ],
            ],
        ];

        // Paginate subscriptions
        $pager = new CustomPaginator(
            [$payment_component, 'paymentCountUserSubscriptions'],
            [$payment_component, 'paymentGetUserSubscriptions'],
            20,
            [],
            [$this->getUser()->id]
        );
        $pager->setCurrentPage($context->getRequest()->asInt($this->getPlatform()->getPageParam(), 1));
        foreach ($pager->getElements() as $subscription) {
            $status = $subscription->paymentSubscriptionStatus();
            if (is_array($status)) {
                $status_color = $status[1];
                $status = $status[0];
            } else {
                $status_color = 'secondary';
            }
            $row = [
                'id' => $this->LinkTo(
                    '#' . $subscription->paymentSubscriptionId(),
                    '#',
                    [
                        'url' => $this->_application->getComponent('Dashboard')->getPanelUrl(
                            'payment_payments',
                            $context->dashboard_panel_link,
                            '/payment_subscriptions',
                            [
                                'subscription_id' => $subscription->paymentSubscriptionId(),
                                'item_id' => $subscription->paymentSubscriptionItemId(),
                            ],
                            true
                        ),
                        'container' => 'modal',
                        'modalSize' => 'lg',
                    ],
                    [
                        'data-modal-title' => sprintf(__('Subscription #%s', 'directories-payments'), $subscription->paymentSubscriptionId()),
                    ]
                ),
                'next' => $subscription->paymentSubscriptionDate(),
                'name' => $this->H($subscription->paymentSubscriptionName()),
                'status' => sprintf('<span class="%1$sbadge %1$sbadge-%2$s">%3$s</span>', DRTS_BS_PREFIX, $status_color, $this->H($status)),
                'total' => $subscription->paymentSubscriptionTotalHtml(),
            ];
            $form['subscriptions']['#options'][$subscription->paymentSubscriptionItemId()] = $row;
        }

        $page_url = $this->_application->getComponent('Dashboard')->getPanelUrl(
            'payment_payments', $context->dashboard_panel_link, '/payment_subscriptions', [], true
        );
        $form['pager'] = [
            '#type' => 'markup',
            '#markup' => '<div class="' . DRTS_BS_PREFIX . 'float-right ' . DRTS_BS_PREFIX . 'mt-4">'
                . $this->PageNav($context->getContainer(), $pager, $page_url)
                . '</div>',
        ];

        return $form;
    }

    protected function _viewSingleSubscription(Context $context, ISubscription $subscription)
    {
        $this->_submitable = false;
        $this->_addDefaultTemplate = false;

        // Init form
        $form = [];

        // Set template for viewing this subscription
        $context->clearTemplates()->setAttributes([
            'content' => $subscription->paymentSubscriptionHtml(),
        ]);

        return $form;
    }
}
