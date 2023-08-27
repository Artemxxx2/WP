<?php
namespace SabaiApps\Directories\Component\Directory\SystemWidget;

class RelatedPostsSystemWidget extends PostsSystemWidget
{   
    protected function _systemWidgetInfo()
    {
        $directory_type_label = $this->_application->Directory_Types_impl($this->_directoryType)->directoryInfo('label');
        $bundle_type_label = $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'label');
        return array(
            'title' => $directory_type_label . ' - ' . sprintf(
                _x('Related %s', 'related posts widget', 'directories'),
                $bundle_type_label
            ),
            'summary' => __('A list of related posts.', 'directories'),
        );
    }
    
    protected function _getWidgetSettings(array $settings)
    {
        $ret = parent::_getWidgetSettings($settings);
        unset($ret['directory']);
        return $ret;
    }
    
    protected function _getCacheId(array $settings)
    {
        if (!$this->_isWidgetRenderable()) return;
        
        // Return false on admin side to prevent fatal error
        return $this->_application->getPlatform()->isAdmin() ? false : parent::_getCacheId($settings) . '_' . $GLOBALS['drts_entity']->getId();
    }
    
    protected function _isWidgetRenderable()
    {
        return isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->getType() === 'post'
            && ($bundle = $this->_application->Entity_Bundle($GLOBALS['drts_entity']))
            && !empty($bundle->info['taxonomies']);
    }
        
    protected function _getWidgetContent(array $settings)
    { 
        if (!$this->_isWidgetRenderable()) return;
        
        return parent::_getWidgetContent($settings);
    }
    
    protected function _getQuery(array $settings)
    {
        $entity = $GLOBALS['drts_entity'];
        $bundle = $this->_application->Entity_Bundle($entity);
        $settings['directory'] = $bundle->group;
        $query = parent::_getQuery($settings)
            ->fieldIsNot('id', $entity->getId()) // exclude self
            ->startCriteriaGroup('OR');
        foreach (array_keys($bundle->info['taxonomies']) as $taxonomy_bundle_type) {
            if ($terms = $entity->getFieldValue($taxonomy_bundle_type)) {
                $term_ids = [];
                foreach ($terms as $term) {
                    $term_ids[] = $term->getId();
                }
                $query->fieldIsIn($taxonomy_bundle_type, $term_ids);
            } else {
                $query->fieldIsNull($taxonomy_bundle_type);
            }
        }
        $query->finishCriteriaGroup();

        return $query;
    }
}