<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class ScriptsHelper
{
    public function help(Application $application, array $options = [])
    {
        $platform = $application->getPlatform();
        $platform->addJsFile('form.min.js', 'drts-form', ['drts']);
        if (empty($options) || in_array('file', $options)) {
            $platform->loadJqueryUiJs(array('widget'))
                ->addJsFile('jquery.iframe-transport.min.js', 'jquery-iframe-transform', 'jquery', null, true, true)
                ->addJsFile('jquery.fileupload.min.js', 'jquery-fileupload', 'jquery-ui-widget', null, true, true)
                ->addJsFile('form-field-file.min.js', 'drts-form-field-file',['jquery-fileupload', 'drts-form']);
        }
        if (empty($options) || in_array('slider', $options) || in_array('range', $options)) {
            $platform->addJsFile('ion.rangeSlider.min.js', 'ion-range-slider', ['jquery'], null, true, true)
                ->addJsFile('form-field-slider.min.js', 'drts-form-field-slider', ['drts-form', 'ion-range-slider'])
                ->addCssFile('ion.rangeSlider.min.css', 'ion-range-slider', null, null, null, true);
            //->addCssFile('ion.rangeSlider.skinNice.min.css', 'ion-range-slider-skin-nice', ['ion-range-slider'], null, null, true);
        }
        if (empty($options) || in_array('tableselect', $options) || in_array('options', $options)) {
            $platform->loadJqueryUiJs(array('sortable'));
        }
        if (empty($options) || in_array('text_maskedinput', $options)) {
            $platform->addJsFile('jquery.maskedinput.min.js', 'jquery-maskedinput', ['jquery'], null, true, true);
        }
        if (empty($options) || in_array('latinise', $options)) {
            $platform->addJsFile('latinise.min.js', 'latinise', null, null, true, true);
        }
        if (empty($options) || in_array('select', $options) || in_array('autocomplete', $options) || in_array('user', $options)) {
            $this->select2($application);
            $platform->addJsFile('form-field-select.min.js', 'drts-form-field-select', ['drts-form']);
        }
        if (empty($options) || in_array('iconpicker', $options)) {
            $this->iconpicker($application);
        }
        if (empty($options) || in_array('colorpicker', $options)) {
            $this->colorpicker($application);
        }
        if (empty($options) || in_array('options', $options)) {
            $platform->addJsFile('form-field-options.min.js', 'drts-form-field-options', ['drts-form']);
        }
        if (empty($options) || in_array('selecthierarchical', $options)) {
            $platform->addJsFile('form-field-selecthierarchical.min.js', 'drts-form-field-selecthierarchical', ['drts-form']);
        }
        if (empty($options) || in_array('datepicker', $options) || in_array('daterange', $options)) {
            $this->date($application);
        }
        if (empty($options) || in_array('timepicker', $options)) {
            $this->time($application);
        }
        if (empty($options) || in_array('addmore', $options)) {
            $platform->addJsFile('form-field-addmore.min.js', 'drts-form-field-addmore', ['drts-form']);
        }
        if (empty($options) || in_array('upload', $options)) {
            $this->file($application);
        }
        if (empty($options) || in_array('editor', $options)) {
            $platform->addJsFile('codemirror.min.js', 'codemirror', null, null, true, true)
                ->addCssFile('codemirror.min.css', 'codemirror', null, null, null, true)
                ->addCssFile('codemirror/theme/mdn-like.min.css', 'codemirror-theme-midnight', ['codemirror'], null, null, true);
        }
        if (empty($options) || in_array('rangelist', $options)) {
            $platform->addJsFile('form-field-rangelist.min.js', 'drts-form-field-rangelist', ['drts-form']);
        }
        if (empty($options) || in_array('multiselect', $options)) {
            $platform->addJsFile('BsMultiSelect.min.js', 'bs-multi-select', ['drts-bootstrap'], null, true, true);
        }
        $application->Action('form_scripts', array($options));
    }

    public function time(Application $application)
    {
        $application->getPlatform()->addJsFile('form-field-timepicker.min.js', 'drts-form-field-timepicker', ['drts-form']);
    }

    public function date(Application $application, $locale = null)
    {
        $theme = 'light';
        $platform = $application->getPlatform();
        $platform->addJsFile(false, 'vanilla-calendar')
            ->addJsFile('vanilla-calendar.min.js', 'vanilla-calendar', null, null, true, true)
            ->addCssFile(false, 'vanilla-calendar')
            ->addCssFile('vanilla-calendar.min.css', 'vanilla-calendar', null, null, null, true)
            ->addCssFile('vanilla-calendar/themes/' . $theme . '.min.css', 'vanilla-calendar-' . $theme, 'vanilla-calendar', null, null, true)
            ->addJsFile('form-field-datepicker.min.js', 'drts-form-field-datepicker', ['vanilla-calendar', 'drts-form']);
    }

    public function isValidDateLocale(Application $application, $locale)
    {
        return in_array($locale, ['ar', 'at', 'be', 'bg', 'bn', 'cat', 'cs', 'cy', 'da', 'de',
            'eo', 'es', 'et', 'fa', 'fi', 'fr', 'gr', 'he', 'hi', 'hr', 'hu', 'id', 'it', 'ja', 'ko', 'lt', 'lv',
            'mk', 'mn', 'ms', 'my', 'nl', 'no', 'pa', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr', 'sv',
            'th', 'tr', 'uk', 'vn', 'zh'
        ]);
    }

    public function dateLocale(Application $application)
    {
        if ($locale = $application->getPlatform()->getLocale()) {
            if (strpos($locale, '_')) {
                $locale = explode('_', $locale)[0];
            }
        }
        return (string)$locale;
    }

    public function locale(Application $application)
    {
        if ($locale = $application->getPlatform()->getLocale()) {
            if (strpos($locale, '_')) {
                $locale = explode('_', $locale)[0];
            }
        }
        return (string)$locale;
    }

    public function file(Application $application)
    {
        $application->getPlatform()->loadJqueryUiJs(['widget', 'sortable'])
            ->addJsFile('jquery.iframe-transport.min.js', 'jquery-iframe-transport', 'jquery', null, true, true)
            ->addJsFile('jquery.fileupload.min.js', 'jquery-fileupload', 'jquery-ui-widget', null, true, true)
            ->addJsFile('form-field-upload.min.js', 'drts-form-field-upload', ['jquery-fileupload', 'jquery-ui-sortable', 'drts-form']);
    }

    public function select2(Application $application, $lang = null)
    {
        $application->getPlatform()->addJsFile('select2.min.js', 'drts-select2', ['jquery'], null, true, true)
            ->addCssFile('select2.min.css', 'drts-select2', null, null, null, true)
            ->addCssFile('form-select2.min.css', 'drts-form-select2', 'drts-select2');
        if (isset($lang)) {
            $application->getPlatform()->addJsFile('select2/i18n/' . $lang . '.min.js', 'drts-select2-' . $lang, 'drts-select2', null, true, true);
        }
    }

    public function iconpicker(Application $application, array $iconsets = [])
    {
        $application->getPlatform()->addJsFile('form-field-picker.min.js', 'drts-form-field-picker', 'drts-form')
            ->addJsFile('form-field-iconpicker.min.js', 'drts-form-field-iconpicker', 'drts-form-field-picker');
        if (empty($iconsets)) {
            $iconsets = ['fontawesome', 'dashicons'];
        }
        foreach ($iconsets as $iconset) {
            $application->getPlatform()->addJsFile('form-field-' . $iconset . '.min.js', 'drts-form-field-' . $iconset, 'drts-form-field-iconpicker');
        }
    }

    public function colorpicker(Application $application)
    {
        $application->getPlatform()->addJsFile('huebee.pkgd.min.js', 'huebee', 'jquery', null, true, true)
            ->addCssFile('huebee.min.css', 'huebee', null, null, null, true)
            ->addJsFile('form-field-colorpicker.min.js', 'drts-form-field-colorpicker', ['drts-form', 'huebee']);
    }

    public function typeahead(Application $application)
    {
        $application->getPlatform()->addJsFile('typeahead.bundle.min.js', 'twitter-typeahead', 'jquery', 'directories', true, true);
    }
}
