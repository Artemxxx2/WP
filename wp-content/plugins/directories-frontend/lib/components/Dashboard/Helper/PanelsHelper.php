<?php
namespace SabaiApps\Directories\Component\Dashboard\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class PanelsHelper
{
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$panels = $application->getPlatform()->getCache('dashboard_panels'))
        ) {
            $panels = [];
            foreach ($application->InstalledComponentsByInterface('Dashboard\IPanels') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
 
                foreach ($application->getComponent($component_name)->dashboardGetPanelNames() as $panel_name) {
                    if (!$panel = $application->getComponent($component_name)->dashboardGetPanel($panel_name)) continue;
                    
                    $panels[$panel_name] = array(
                        'component' => $component_name,
                        'weight' => (null !== $weight = $panel->dashboardPanelInfo('weight')) ? $weight : 9,
                        'labellable' => $panel->dashboardPanelInfo('labellable') !== false,
                    );
                }
            }
            
            // Sort panels by weight
            uasort($panels, function ($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1; });
            foreach (array_keys($panels) as $panel_name) {
                unset($panels[$panel_name]['weight']);
            }
            
            $application->getPlatform()->setCache($panels, 'dashboard_panels');
        }

        return $panels;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Dashboard\Panel\IPanel interface for a given panel name
     * @param Application $application
     * @param string $panel
     */
    public function impl(Application $application, $panel, $returnFalse = false)
    {
        if (!isset($this->_impls[$panel])) {            
            if ((!$panels = $application->Dashboard_Panels())
                || !isset($panels[$panel]['component'])
                || !$application->isComponentLoaded($panels[$panel]['component'])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid panel: %s', $panel));
            }
            $this->_impls[$panel] = $application->getComponent($panels[$panel]['component'])->dashboardGetPanel($panel);
        }

        return $this->_impls[$panel];
    }
    
    public function js(Application $application, $container, $scroll = false, $pushState = true)
    {
        ob_start();?>
<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    var $ = jQuery;
    $('.drts-dashboard-links').on('click', '.drts-dashboard-panel-link', function (e) {
        var $this = $(this);
        e.preventDefault();
        if ($this.data('url')) {
            window.location = $this.data('url');
            return;
        }
        $this.closest('.drts-dashboard-links')
            .find('.drts-dashboard-panel-link.<?php echo DRTS_BS_PREFIX;?>active')
            .removeClass('<?php echo DRTS_BS_PREFIX;?>active');
        $this.addClass('<?php echo DRTS_BS_PREFIX;?>active');
        DRTS.ajax({
            container: '<?php echo $container;?>',
            scroll: <?php if ($scroll):?>true<?php else:?>false<?php endif;?>,
            pushState: <?php if ($pushState):?>true<?php else:?>false<?php endif;?>,
            cache: false,
            trigger: $this
        });
    });
});
</script>
<?php
        return ob_get_clean();
    }
}