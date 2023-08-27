<?php
namespace SabaiApps\Directories\Component\WordPressContent;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class AbstractHomePage
{
    /**
     * @var Sabai
     */
    protected $_application;
    /**
     *
     * @var bool
     */
    private $_scriptLoaded = false;
    
    public function __construct(Application $application, array $methods = [])
    {
        $this->_application = $application;
        foreach ($methods as $method => $full_width) {
            $this->_addAction($method, $full_width);
        }
    }
    
    protected function _addAction($method, $isFullWidth = false)
    {
        foreach ((array)$method as $_method) {
            add_action('homepage', array($this, $_method));
        }
        if ($isFullWidth
            && !$this->_scriptLoaded
        ) {
            add_action('wp_enqueue_scripts', array($this, '_scripts'), 10);
            $this->_scriptLoaded = true;
        }
    }
    
    public function _scripts()
    {
        $this->_application->getPlatform()->addJsFile('wordpress-homepage.min.js', 'drts-wordpress-homepage', 'jquery');
    }
    
    protected static function _display(Application $application, $methodName, Entity\Model\Bundle $bundle, array $settings, $title = null, $fullWidth = false)
    {
        // Properly namespace action name
        $name = 'wordpress_homepage_' . $methodName;

        $cache = $application->Filter(
            'wordpress_homepage_cache_lifetime',
            $application->getPlatform()->isDebugEnabled() && $application->getUser()->isAdministrator() ? false : true,
            [$methodName, $bundle]
        );
        
        $args = $application->Filter($name, array(
            'settings' => $settings,
            'title' => $title,
            'cache' => $cache,
            'full_width' => $fullWidth,
        ), [$methodName, $bundle]);
        
        $content = $application->getPlatform()->render(
            $bundle->getPath(),
            ['settings' => $args['settings']],
            [
                'cache' => $args['cache'],
            ]
        );
        
        $class = 'drts-' . str_replace('_', '-', $name);
        if ($args['full_width']) $class .= ' drts-wordpress-homepage-section-full-width';
        
        echo '<section class="storefront-product-section drts-wordpress-homepage-section ' . $class . '" aria-label="' . esc_attr(ucwords(str_replace('_', ' ', $methodName))) . '">';
        $application->Action($name . '_before');
        if (isset($args['title'])) {
            echo '<h2 class="section-title drts-wordpress-homepage-section-title">' . esc_html(sprintf($args['title'], $bundle->getLabel('singular'))) . '</h2>';
            do_action($name . '_after_title');
        }
        echo $content;
        $application->Action($name . '_after');
        echo '</section>';
    }
}