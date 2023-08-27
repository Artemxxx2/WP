<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\lib\Color;

class ColorType extends AbstractValueType implements IConditionable
{
    use ConditionableDefaultTrait;

    protected static $_labels, $_colors = [
        'pink' => ['FFC0CB', 'FFB6C1', 'FF69B4', 'FF1493', 'DB7093', 'C71585'],
        'red' => ['FFA07A', 'FA8072', 'E9967A', 'F08080', 'CD5C5C', 'DC143C', 'FF0000', 'B22222', '8B0000'],
        'orange' => ['FFA500', 'FF8C00', 'FF7F50', 'FF6347', 'FF4500'],
        'yellow' => ['FFD700', 'FFFF00', 'FFFFE0', 'FFFACD', 'FAFAD2', 'FFEFD5', 'FFE4B5', 'FFDAB9', 'EEE8AA', 'F0E68C', 'BDB76B'],
        'green' => ['ADFF2F', '7FFF00', '7CFC00', '00FF00', '32CD32', '98FB98', '90EE90', '00FA9A', '00FF7F', '3CB371', '2E8B57',
            '228B22', '008000', '006400', '9ACD32', '6B8E23', '556B2F', '66CDAA', '8FBC8F', '20B2AA', '008B8B', '008080'],
        'cyan' => ['00FFFF', '00FFFF', 'E0FFFF', 'AFEEEE', '7FFFD4', '40E0D0', '48D1CC', '00CED1'],
        'blue' => ['5F9EA0', '4682B4', 'B0C4DE', 'ADD8E6', 'B0E0E6', '87CEFA', '87CEEB', '6495ED', '00BFFF', '1E90FF', '4169E1',
            '0000FF', '0000CD', '00008B', '000080', '191970'],
        'purple' => ['E6E6FA', 'D8BFD8', 'DDA0DD', 'DA70D6', 'EE82EE', 'FF00FF', 'FF00FF', 'BA55D3', '9932CC', '9400D3',
            '8A2BE2', '8B008B', '800080', '9370DB', '7B68EE', '6A5ACD', '483D8B', '663399', '4B0082'],
        'brown' => ['FFF8DC', 'FFEBCD', 'FFE4C4', 'FFDEAD', 'F5DEB3', 'DEB887', 'D2B48C', 'BC8F8F', 'F4A460', 'DAA520', 'B8860B',
            'CD853F', 'D2691E', '808000', '8B4513', 'A0522D', 'A52A2A', '800000'],
        'black' => ['000000', '080808', '101010', '181818', '202020'],
        'grey' => ['DCDCDC', 'D3D3D3', 'C0C0C0', 'A9A9A9', '696969', '808080', '778899', '708090', '2F4F4F'],
        'white' => ['FFFFFF', 'FFFAFA', 'F0FFF0', 'F5FFFA', 'F0FFFF', 'F0F8FF', 'F8F8FF', 'F5F5F5', 'FFF5EE', 'F5F5DC', 'FDF5E6',
            'FFFAF0', 'FFFFF0', 'FAEBD7', 'FAF0E6', 'FFF0F5', 'FFE4E1'],
    ];
    protected $_palette;
    
    public static function colors()
    {
        return self::$_colors;
    }
    
    public static function labels()
    {
        if (!isset(self::$_labels)) {
            self::$_labels = [
                'pink' => __('Pink', 'directories'),
                'purple' => __('Purple', 'directories'),
                'red' => __('Red', 'directories'),
                'orange' => __('Orange', 'directories'),
                'yellow' => __('Yellow', 'directories'),
                'green' => __('Green', 'directories'),
                'cyan' => __('Cyan', 'directories'),
                'blue' => __('Blue', 'directories'),
                'brown' => __('Brown', 'directories'),
                'white' => __('White', 'directories'),
                'grey' => __('Grey', 'directories'),
                'black' => __('Black', 'directories'),
            ];
        }
        return self::$_labels;
    }
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Color', 'directories'),
            'icon' => 'fas fa-paint-brush',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 10,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
                'closest' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 6,
                    'notnull' => true,
                    'was' => 'closest',
                    'default' => '',
                ),
                'name' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 10,
                    'notnull' => true,
                    'was' => 'name',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'name' => array(
                    'fields' => array('name' => array('sorting' => 'ascending')),
                    'was' => 'name',
                ),
            ),
        );
    }
    
    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        $colors = self::colors();
        foreach ($values as $value) {
            $name = '';
            if (is_array($value)) {
                if (!isset($value['value'])
                    || isset($ret[$value['value']])
                ) continue;
                
                $closest = isset($value['closest']) ? $value['closest'] : $this->_getClosestColor($value['value']);
                $value = $value['value'];
            } else {
                if (isset($ret[$value])) continue;
                
                $closest = $this->_getClosestColor($value);
            }
            if ($closest !== '') {
                foreach (array_keys($colors) as $_name) {
                    if (in_array($closest, $colors[$_name])) {
                        $name = $_name;
                        break;
                    }
                }
            }
            $ret[$value] = [
                'value' => $value,
                'closest' => $closest,
                'name' => $name,
            ];
        }

        return array_values($ret);
    }

    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $value['value'];
        }
    }
    
    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {   
        $new = [];
        foreach ($valueToSave as $value) {
            $new[] = $value['value'];
        }
        return $currentLoadedValue !== $new;
    }
    
    protected function _getClosestColor($color)
    {
        if (!isset($this->_palette)) {
            $this->_palette = array_map('hexdec', call_user_func_array('array_merge', array_values(self::colors())));
        }
        if (strpos($color, '#') === 0) $color = substr($color, 1);
        $index = (new Color(hexdec($color)))->getClosestMatch($this->_palette);

        if ($index === null
            || !isset($this->_palette[$index])
        ) return '';
        
        return strtoupper(str_pad(dechex($this->_palette[$index]),  6, '0', STR_PAD_LEFT));
    }
}