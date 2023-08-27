<?php
namespace SabaiApps\Directories\Component\Review\FakerGenerator;

use SabaiApps\Directories\Component\Faker\Generator\AbstractGenerator;
use SabaiApps\Directories\Component\Field;

class ReviewFakerGenerator extends AbstractGenerator
{   
    protected function _fakerGeneratorInfo()
    {
        switch ($this->_name) {
            case 'review_rating':
                return array(
                    'field_types' => array($this->_name),
                    'default_settings' => [],
                );
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        switch ($this->_name) {
            case 'review_rating':
                $ratings = [];
                foreach (array_keys($this->_application->Review_Criteria($field->Bundle, false, false, false)) as $rating_name) {
                    $ratings[$rating_name] = mt_rand(1, 5);
                }
                return array($ratings);
        }
    }
}