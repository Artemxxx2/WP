<?php
namespace SabaiApps\Directories\Component\Form;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;

class FormComponent extends AbstractComponent implements IFields, System\IMainRouter
{
    const VERSION = '1.3.108', PACKAGE = 'directories';
    const FORM_BUILD_ID_NAME = '_drts_form_build_id', FORM_SUBMIT_BUTTON_NAME = '_drts_form_submit';

    public static function description()
    {
        return 'Provides API for building and displaying HTML forms.';
    }

    public function formGetFieldTypes()
    {
        return ['textarea', 'radio', 'radios', 'checkbox', 'checkboxes', 'select',
            'hidden', 'item', 'markup', 'password', 'textfield', 'fieldset', 'submit',
            'grid', 'tableselect', 'token', 'options', 'text', 'search',
            'url', 'email', 'number', 'range', 'address', 'slider', 'iconpicker', 'file',
            'autocomplete', 'user', 'selecthierarchical', 'colorpicker', 'colorpalette',
            'sortablecheckboxes', 'lengths', 'addmore', 'timepicker', 'datepicker', 'editor',
            'buttons', 'rangelist', 'monthpicker', 'daterangepicker'
        ];
    }

    public function formGetField($type)
    {
        switch ($type) {
            case 'textarea':
                return new Field\TextareaField($this->_application);
            case 'radio':
                return new Field\RadioField($this->_application);
            case 'radios':
                return new Field\RadiosField($this->_application);
            case 'checkbox':
                return new Field\CheckboxField($this->_application);
            case 'checkboxes':
                return new Field\CheckboxesField($this->_application);
            case 'sortablecheckboxes':
                return new Field\SortableCheckboxesField($this->_application);
            case 'select':
                return new Field\SelectField($this->_application);
            case 'hidden':
                return new Field\HiddenField($this->_application);
            case 'item':
                return new Field\ItemField($this->_application);
            case 'markup':
                return new Field\MarkupField($this->_application);
            case 'password':
                return new Field\PasswordField($this->_application);
            case 'textfield':
            case 'text':
            case 'search':
            case 'url':
            case 'email':
            case 'number':
                return new Field\TextField($this->_application);
            case 'range':
                return new Field\RangeField($this->_application);
            case 'fieldset':
                return new Field\FieldsetField($this->_application);
            case 'submit':
                return new Field\SubmitField($this->_application);
            case 'grid':
                return new Field\GridField($this->_application);
            case 'tableselect':
                return new Field\TableSelectField($this->_application);
            case 'token':
                return new Field\TokenField($this->_application);
            case 'options':
                return new Field\OptionsField($this->_application);
            case 'address':
                return new Field\AddressField($this->_application);
            case 'slider':
                return new Field\SliderField($this->_application);
            case 'file':
                return new Field\FileField($this->_application);
            case 'autocomplete':
                return new Field\AutocompleteField($this->_application);
            case 'user':
                return new Field\UserField($this->_application);
            case 'selecthierarchical':
                return new Field\SelectHierarchicalField($this->_application);
            case 'colorpicker':
                return new Field\ColorPickerField($this->_application);
            case 'colorpalette':
                return new Field\ColorPaletteField($this->_application);
            case 'lengths':
                return new Field\LengthsField($this->_application);
            case 'addmore':
                return new Field\AddMoreField($this->_application);
            case 'datepicker':
                return new Field\DatePickerField($this->_application);
            case 'timepicker':
                return new Field\TimePickerField($this->_application);
            case 'editor':
                return new Field\EditorField($this->_application);
            case 'iconpicker':
                return new Field\IconPickerField($this->_application);
            case 'buttons':
                return new Field\ButtonsField($this->_application);
            case 'rangelist':
                return new Field\RangeListField($this->_application);
            case 'monthpicker':
                return new Field\MonthPickerField($this->_application);
            case 'daterangepicker':
                return new Field\DateRangePickerField($this->_application);
        }
    }

    public function setFormStorage($formBuildId, $storage, $useOption = false)
    {
        if ($useOption) {
            $this->_application->getPlatform()->setOption('form_' . $formBuildId, $storage, false);
        } else {
            $this->_application->getPlatform()->setSessionVar('form_' . $formBuildId, $storage);
        }
    }

    public function getFormStorage($formBuildId, $useOption = false)
    {
        if ($useOption) {
            return $this->_application->getPlatform()->getOption('form_' . $formBuildId);
        }
        return $this->_application->getPlatform()->getSessionVar('form_' . $formBuildId);
    }

    public function clearFormStorage($formBuildId, $useOption = false)
    {
        if ($useOption) {
            $this->_application->getPlatform()->deleteOption('form_' . $formBuildId);
        }
        return $this->_application->getPlatform()->deleteSessionVar('form_' . $formBuildId);
    }

    public function systemMainRoutes($lang = null)
    {
        return [
            '/_drts/form/upload' => [
                'controller' => 'UploadFile',
                'type' => Application::ROUTE_CALLBACK,
            ],
            '/_drts/form/user' => [
                'controller' => 'User',
                'type' => Application::ROUTE_CALLBACK,
            ],
        ];
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route){}

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route){}
}
