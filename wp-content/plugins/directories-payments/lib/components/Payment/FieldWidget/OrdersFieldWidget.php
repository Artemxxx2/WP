<?php
namespace SabaiApps\Directories\Component\Payment\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class OrdersFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Orders', 'directories-payments'),
            'field_types' => array($this->_name),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!isset($entity)
            || (!$payment_component = $this->_application->getComponent('Payment')->getPaymentComponent(false))
            || empty($field->Bundle->info['payment_enable'])
        ) return;

        $orders = [];
        foreach ($payment_component->paymentGetOrders($entity->getId()) as $order) {
            $status = $order->paymentOrderStatus();
            if (is_array($status)) {
                $status_color = $status[1];
                $status = $status[0];
            } else {
                $status_color = 'secondary';
            }
            $orders[$order->paymentOrderId()] = array(
                'id' => '<a href="' . $order->paymentOrderAdminUrl() . '">#' . $this->_application->H($order->paymentOrderId(true)) . '</a>',
                'date' => $this->_application->System_Date($order->paymentOrderTimestamp()),
                'name' => $this->_application->H($order->paymentOrderName()),
                'action' => $this->_application->H($this->_application->Payment_Util_actionLabel($order->paymentOrderAction(), $order->paymentOrderWasItemDeactivated())),
                'status' => '<span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-' . $status_color . '">' . $this->_application->H($status) . '</span>',
                'total' => $order->paymentOrderTotalHtml(),
            );
        }
        if (empty($orders)) return;

        return array(
            '#type' => 'tableselect',
            '#header' => array(
                'id' => __('Order ID', 'directories-payments'),
                'date' => __('Date', 'directories-payments'),
                'action' => __('Action', 'directories-payments'),
                'name' => __('Name', 'directories-payments'),
                'total' => __('Total', 'directories-payments'),
                'status' => __('Status', 'directories-payments'),
            ),
            '#options' => $orders,
            '#disabled' => true,
            '#class' => 'drts-data-table',
            '#size' => 'sm',
        );
    }
}
