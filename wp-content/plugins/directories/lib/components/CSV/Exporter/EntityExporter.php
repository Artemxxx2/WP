<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

use SabaiApps\Directories\Component\Entity;

class EntityExporter extends AbstractExporter
{    
    protected function _csvExporterInfo()
    {
        switch ($this->_name) {
            case 'entity_featured':
                $columns = array(
                    'value' => __('Priority', 'directories'),
                    'featured_at' => __('Featured Date', 'directories'),
                    'expires_at' => __('End Date', 'directories'),
                );
                break;
            case 'entity_activity':
                $columns = array(
                    'active_at' => __('Last Active Date', 'directories'),
                    'edited_at' => __('Edited Date', 'directories'),
                );
                break;           
            default:
                $columns = null;
        }
        return array(
            'field_types' => array($this->_name),
            'columns' => $columns,
        );
    }
    
    public function csvExporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'entity_post_views':
                return empty($bundle->info['parent']) && !empty($bundle->info['public']);
            case 'entity_slug':
                return empty($bundle->info['parent']);
            case 'entity_terms':
                // Skip taxonomies that are assigned through another field
                return ($bundle = $this->_application->Entity_Bundle($field->getFieldData('_bundle_name')))
                    && false !== $this->_application->Entity_BundleTypeInfo($bundle, 'taxonomy_assignable');
            case 'entity_term_parent':
                return !empty($bundle->info['is_hierarchical']);
        }
        return true;
    }
    
    public function csvExporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'entity_author':
                return $this->_getUserSettingsForm();
            case 'entity_published':
            case 'entity_activity':
                return $this->_getDateFormatSettingsForm($parents);
            case 'entity_featured':
                if (in_array($column, array('featured_at', 'expires_at'))) {
                    return $this->_getDateFormatSettingsForm($parents);
                }
                return;
            case 'entity_child_count':
                return array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => $title = __('Content type/count separator', 'directories'),
                        '#description' => __('Enter the character used to separate the content type and count.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents, array('separator' => $title));
            case 'entity_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Content item ID type', 'directories'),
                        '#description' => __('Select the type of data used to specify exported items.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );
            case 'entity_term_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Parent term data type', 'directories'),
                        '#description' => __('Select the type of data used to specify terms.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                            'title' => __('Title', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );  
            case 'entity_terms':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Taxonomy term data type', 'directories'),
                        '#description' => __('Select the type of data used to specify terms.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                            'title' => __('Title', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents);
            case 'entity_term_content_count':
                return array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => $title = __('Content type/count separator', 'directories'),
                        '#description' => __('Enter the character used to separate the content type and count.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents, array('separator' => $title));
            case 'entity_reference':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Content item ID type', 'directories'),
                        '#description' => __('Select the type of data used to specify exported items.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }
    
    public function csvExporterDoExport(Entity\Model\Field $field, array $settings, $value, array $columns, array &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'entity_featured':
                $ret = [];
                foreach ($columns as $column) {
                    if (in_array($column, array('featured_at', 'expires_at'))
                        && $settings[$column]['date_format'] === 'string'
                    ) {
                        if (!empty($value[0][$column])
                            && false !== ($_value = @date($settings[$column]['date_format_php'], $value[0][$column]))
                        ) {
                            $ret[$column] = $_value;
                        }
                    } else {
                        $ret[$column] = $value[0][$column];
                    }
                }
                return $ret;
            case 'entity_activity':
                $ret = [];
                foreach ($columns as $column) {
                    if ($settings[$column]['date_format'] === 'string') {
                        if (!empty($value[0][$column])
                            && false !== ($_value = @date($settings[$column]['date_format_php'], $value[0][$column]))
                        ) {
                            $ret[$column] = $_value;
                        }
                    } else {
                        $ret[$column] = $value[0][$column];
                    }
                }
                return $ret;
            case 'entity_parent':
                return $settings['type'] === 'slug' ? $value[0]->getSlug() : $value[0]->getId();
            case 'entity_author':
                if ($settings['id_format'] === 'username') {
                    return is_object($value) || ($value = $this->_application->UserIdentity($value)) ? $value->username : null;
                }
                return is_object($value) ? $value->id : $value;
            case 'entity_published':
                if ($settings['date_format'] === 'string') {
                    $ret = @date($settings['date_format_php'], $value);
                    return false !== $ret ? $ret : '';
                }
                return $value;
            case 'entity_child_count':
                foreach ($value[0] as $child_bundle_name => $count) {
                    $ret[] = $child_bundle_name . $settings['separator'] . $count;
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
            case 'entity_term_parent':
                if (empty($value)
                    || (!$parent = $this->_application->Entity_Entity($field->Bundle->entitytype_name, $value, false))
                ) return '';
                
                switch ($settings['type']) {
                    case 'slug':
                        $method = 'getSlug';
                        break;
                    case 'title':
                        $method = 'getTitle';
                        break;
                    case 'id':
                    default:
                        $method = 'getId';
                        break;
                }
                return $parent->$method();
            case 'entity_terms':
                switch ($settings['type']) {
                    case 'slug':
                        $method = 'getSlug';
                        break;
                    case 'title':
                        $method = 'getTitle';
                        break;
                    case 'id':
                    default:
                        $method = 'getId';
                        break;
                }
                $ret = [];
                foreach ($value as $_value) {
                    $ret[] = $_value->$method();
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
            case 'entity_term_content_count':
                $ret = [];
                foreach ($value[0] as $content_bundle_name => $count) {
                    if (strpos($content_bundle_name, '_') === 0) continue;
                    
                    $ret[] = $content_bundle_name . $settings['separator'] . $count . $settings['separator'] . $value[0]['_' . $content_bundle_name];
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
            case 'entity_reference':
                switch ($settings['type']) {
                    case 'slug':
                        $method = 'getSlug';
                        break;
                    case 'id':
                    default:
                        $method = 'getId';
                        break;
                }
                $ret = [];
                if (is_array($value)) { // a user has reported an error $value not being an array for some reason
                    foreach ($value as $_value) {
                        $ret[] = $_value->$method();
                    }
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
            default:
                return $value;
        }
    }
}