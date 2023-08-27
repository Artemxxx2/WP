<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class AuthorFieldType extends Field\Type\AbstractType implements
    Field\Type\ISchemable,
    Field\Type\IQueryable,
    Field\Type\IHumanReadable,
    IPersonalDataAuthorFieldType,
    Field\Type\IPersonalData,
    Field\Type\IConditionable
{
    use Field\Type\QueryableUserTrait;

    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Author', 'directories'),
            'creatable' => false,
            'icon' => 'fas fa-user',
            'admin_only' => true,
            'entity_types' => ['post'],
        ];
    }

    public function fieldSchemaProperties()
    {
        return array('author');
    }

    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        return array(array(
            '@type' => 'Person',
            'name' => $this->_application->Entity_Author($entity)->name,
        ));
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        return $this->_application->Entity_Author($entity)->name;
    }

    public function fieldPersonalDataQuery(Query $query, $fieldName, $email, $userId)
    {
        $query->fieldIs($fieldName, $userId);
    }

    public function fieldPersonalDataAnonymize(IField $field, IEntity $entity)
    {
        return 0;
    }

    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        if (!$isServerSide) return;

        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter user IDs, usernames, and/or "_current_user_" separated with commas.', 'directories'),
                'example' => 7,
            ],
        ];
    }

    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;

                    $value = array_map('trim', $value);
                } else {
                    $value = [$value];
                }
                foreach (array_keys($value) as $k) {
                    if ($value[$k] === '_current_user_') {
                        $value[$k] = (int)$this->_application->getUser()->id;
                    } elseif (!is_numeric($value[$k])) {
                        if (!$user = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchByUsername($value[$k])) {
                            unset($value[$k]); // invalid username
                            continue;
                        }
                        $value[$k] = (int)$user->id;
                    } else {
                        $value[$k] = (int)$value[$k];
                    }
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        $author_id = (int)$entity->getAuthorId();
        switch ($rule['type']) {
            case 'value':
            case '!value':
            case 'one':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ((array)$rule['value'] as $rule_value) {
                    if ($author_id === $rule_value) {
                        if ($rule['type'] === '!value') return false;
                        if ($rule['type'] === 'one') return true;
                        continue;
                    }
                    // One of rules did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All rules matched or did not match.
                return $rule['type'] !== 'one' ? true : false;
            case 'empty':
                return empty($author_id) === $rule['value'];
            case 'filled':
                return !empty($author_id) === $rule['value'];
            default:
                return false;
        }
    }

    public function fieldPersonalDataExport(IField $field, IEntity $entity)
    {

    }

    public function fieldPersonalDataErase(IField $field, IEntity $entity)
    {
        return 0;
    }
}
