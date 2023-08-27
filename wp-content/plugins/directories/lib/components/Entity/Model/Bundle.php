<?php
namespace SabaiApps\Directories\Component\Entity\Model;

class Bundle extends Base\Bundle
{
    private static $_infoKeys, $_infoUserKeys;

    public function getPath($permalink = false, $lang = null)
    {
        return $this->_model->Entity_Bundle_path($this, $permalink, $lang);
    }

    public function getAdminPath()
    {
        $path = '/' . strtolower($this->component);
        if (!empty($this->info['parent'])
            || !empty($this->info['is_taxonomy'])
        ) {
            $path .= '/' . $this->info['slug'];
        }
        return $path;
    }
    
    public function getLabel($type = null, $language = null)
    {
        $key = isset($type) ? 'label_' . $type : 'label';
        $label = $this->_model->System_TranslateString($this->info[$key], $this->name . '_' . $key, 'entity_bundle', $language);
        
        // Allow including parent bundle label if child bundle
        if ($type === 'add'
            && !empty($this->info['parent'])
            && ($parent_bundle = $this->_model->Entity_Bundle($this->info['parent']))
        ) {
            $label = sprintf($label, $parent_bundle->getLabel('singular', $language));
        }
        return $label;
    }
    
    public function setInfo(array $info, $overwrite = true)
    {
        $info = array_intersect_key($info, array_merge(array_flip($this->_getBundleInfoKeys()), array_flip($this->_getBundleInfoUserKeys()))); // only allow certain keys
        if ($this->info) {
            if (!$overwrite) {
                // Preserve user editable info
                $info = array_intersect_key($this->info, array_flip($this->_getBundleInfoUserKeys())) + $info;
            }
            $info += $this->info;
        }
        $this->info = $info;
        
        // Register labels for string translation
        foreach (array_keys($info) as $key) {
            if (!is_string($info[$key])
                || strpos($key, 'label') !== 0 // key must start with "label"
            ) continue;
            
            $this->_model->Platform()->registerString($info[$key], $this->name . '_' . $key, 'entity_bundle');
        }
        
        return $this;
    }

    protected function _getBundleInfoKeys()
    {
        if (!isset(self::$_infoKeys)) {
            $keys = array(
                'public', 'no_content', 'is_primary', 'is_taxonomy', 'is_hierarchical', 'taxonomies', 'parent', 'system', 'is_user', 'internal',
            );
            self::$_infoKeys = $this->_model->Filter('entity_bundle_info_keys', $keys);
        }
        return self::$_infoKeys;
    }

    protected function _getBundleInfoUserKeys()
    {
        if (!isset(self::$_infoUserKeys)) {
            $keys = array(
                'slug', 'label', 'label_singular', 'label_add', 'label_all', 'label_select', 'label_count', 'label_count2', 'label_page', 'label_search',
                'no_title', 'title_field', 'entity_schemaorg', 'entity_opengraph', 'privatable',
            );
            self::$_infoUserKeys = $this->_model->Filter('entity_bundle_info_user_keys', $keys);
        }
        return self::$_infoUserKeys;
    }
    
    public function getGroupLabel($language = null)
    {
        if ($this->group) {        
            switch ($this->component) {
                case 'Directory':
                    if ($directory = $this->_model->Directory_Directory($this->group)) {
                        return $directory->getLabel($language);
                    }
                    break;
            }
        }
        return $this->getLabel(null, $language);
    }
}

class BundleRepository extends Base\BundleRepository
{
}
