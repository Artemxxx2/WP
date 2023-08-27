<?php
namespace SabaiApps\Directories\Component\WooCommerce;

use SabaiApps\Directories\Component\Payment\IPlan;

class PaymentPlan implements IPlan
{
    protected $_product;
    
    public function __construct(IProduct $product)
    {
        $this->_product = $product;
    }

    public function getProduct()
    {
        return $this->_product;
    }
    
    public function paymentPlanId()
    {
        return $this->_product->get_id();
    }

    public function paymentPlanName()
    {
        return $this->_product->get_slug();
    }
    
    public function paymentPlanBundleName()
    {
        $plan_type = $this->paymentPlanType();
        return $plan_type === 'base' ?
            substr($this->_product->get_type(), strlen('drts_')) : // remove prefix
            substr($this->_product->get_type(), strlen('drts_'), -1 * (strlen($plan_type) + 2)); // remove prefix and "__{$plan_type}" suffix
    }
        
    public function paymentPlanType()
    {
        return $this->_product->get_sabai_plan_type();
    }
        
    public function paymentPlanTitle()
    {
        return $this->_product->get_name();
    }
    
    public function paymentPlanDescription()
    {
        return ($desc = $this->_product->get_short_description()) ? $desc : $this->_product->get_description();
    }
    
    public function paymentPlanFeatures()
    {
        $post_id = $this->_product->get_id();
        if (defined('WPML_PLUGIN_BASENAME')
            && ($lang = apply_filters('wpml_default_language', null))
            && ($original_post_id = apply_filters('wpml_object_id', $post_id, 'product', false, $lang))
        ) {
            $post_id = $original_post_id;
        }
        return (array)get_post_meta($post_id, '_drts_entity_features', true);
    }
    
    public function paymentPlanIsFeatured()
    {
        return $this->_product->get_featured();
    }
    
    public function paymentPlanPrice($html = false)
    {
        if (!$html) return $this->_product->get_price();
        
        // Remove unwanted zero from price html
        add_filter('woocommerce_price_trim_zeros', '__return_true', 99999);
        $html = $this->_product->get_price_html();
        // Remove filter added
        remove_filter('woocommerce_price_trim_zeros', '__return_true', 99999);
        return $html;
    }
    
    public function paymentPlanTotal()
    {
        return wc_get_price_including_tax($this->_product);
    }

    public function paymentPlanIsPurchasable()
    {
        return $this->_product->is_purchasable()
            && 'publish' === $this->_product->get_status();
    }    

    public function paymentPlanIsFree()
    {
        $price = $this->paymentPlanPrice();
        return is_numeric($price) && (int)$price === 0;
    }
}
