<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Payment\IOrder;
use SabaiApps\Framework\Paginator\CustomPaginator;

class DashboardOrders extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        $payment_component = $this->getComponent('Payment')->getPaymentComponent(true);

        if (($order_id = $context->getRequest()->asStr('order_id'))
            && ($order = $payment_component->paymentGetOrder($order_id))
        ) {
            // Show single user order
            return $this->_viewSingleOrder($context, $order);
        }

        // Show all user orders

        // Init form
        $form = [
            'orders' => [
                '#type' => 'tableselect',
                '#header' => [
                    'id' => __('ID', 'directories-payments'),
                    'date' => __('Date', 'directories-payments'),
                    'name' => __('Name', 'directories-payments'),
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
                        'status' => ['class' => 'drts-paidlistings-orders-status'],
                    ],
                ],
            ],
        ];

        // Paginate orders
        $pager = new CustomPaginator(
            [$payment_component, 'paymentCountUserOrders'],
            [$payment_component, 'paymentGetUserOrders'],
            20,
            [],
            [$this->getUser()->id]
        );
        $pager->setCurrentPage($context->getRequest()->asInt($this->getPlatform()->getPageParam(), 1));
        foreach ($pager->getElements() as $order) {
            $status = $order->paymentOrderStatus();
            if (is_array($status)) {
                $status_color = $status[1];
                $status = $status[0];
            } else {
                $status_color = 'secondary';
            }
            $row = [
                'id' => $this->LinkTo(
                    '#' . $order->paymentOrderId(true),
                    '#',
                    [
                        'url' => $this->_application->getComponent('Dashboard')->getPanelUrl(
                            'payment_payments', $context->dashboard_panel_link, '/payment_orders', ['order_id' => $order->paymentOrderId()], true
                        ),
                        'container' => 'modal',
                        'modalSize' => 'lg',
                    ],
                    [
                        'data-modal-title' => sprintf(__('Order #%s', 'directories-payments'), $order->paymentOrderId(true)),
                    ]
                ),
                'date' => ($ts = $order->paymentOrderTimestamp()) ? $this->_application->System_Date($ts) : '',
                'name' => $this->H($order->paymentOrderName() . ' - ' . $this->_application->Payment_Util_actionLabel($order->paymentOrderAction())),
                'status' => sprintf('<span class="%1$sbadge %1$sbadge-%2$s">%3$s</span>', DRTS_BS_PREFIX, $status_color, $this->H($status)),
                'total' => $order->paymentOrderTotalHtml(),
            ];
            $form['orders']['#options'][$order->paymentOrderId()] = $row;
        }

        $page_url = $this->_application->getComponent('Dashboard')->getPanelUrl(
            'payment_payments', $context->dashboard_panel_link, '/payment_orders', [], true
        );
        $form['pager'] = [
            '#type' => 'markup',
            '#markup' => '<div class="' . DRTS_BS_PREFIX . 'float-right ' . DRTS_BS_PREFIX . 'mt-4">'
                . $this->PageNav($context->getContainer(), $pager, $page_url)
                . '</div>',
        ];

        return $form;
    }

    protected function _viewSingleOrder(Context $context, IOrder $order)
    {
        $this->_submitable = false;
        $this->_addDefaultTemplate = false;

        // Init form
        $form = [];

        // Set template for viewing this order
        $context->clearTemplates()->setAttributes([
            'content' => $order->paymentOrderHtml(),
        ]);

        return $form;
    }
}
