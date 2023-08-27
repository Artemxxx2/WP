<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class EditorField extends AbstractField
{
    protected static $_elements = [];
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }
        if (!isset($data['#language'])
            || !in_array($data['#language'], array('css', 'javascript', 'xml'))
        ) {
            $data['#language'] = 'xml';
        }
        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = [];
        }
        self::$_elements[$form->settings['#id']][$data['#id']] = $data['#language'];
        
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
    }
    
    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (false !== $validated = $this->_application->Form_Validate_text($form, $value, $data, true, true)) {
            $value = $validated;
        }
        if (isset($data['#file_path'])) {
            if (strlen($value)) {
                if (false === file_put_contents($data['#file_path'], $value)) {
                    $form->setError('Error writing into file: ' . $data['#file_path'] . '. Make sure the file can be created and is writeable.');
                } else {
                    $value = null;
                }
            } else {
                @unlink($data['#file_path']);
            }
        }
    }
    
    public function formFieldRender(array &$data, Form $form)
    {
        if (isset($data['#default_value'])) {
            $content = $data['#default_value'];
        } elseif (isset($data['#file_path'])) {
            if (file_exists($data['#file_path'])) {
                $content = @file_get_contents($data['#file_path']);
            }
        }
        $html = sprintf(
            '<textarea id="%s-editor" name="%s"%s>%s</textarea>',
            $data['#id'],
            $data['#name'],
            $this->_application->Attr($data['#attributes']),
            isset($content) && strlen($content) ? $this->_application->H($content) : ''
        );
        $this->_render($html, $data, $form);
    }
    
    public function preRenderCallback(Form $form)
    {
        // Add CodeMirror
        $this->_application->getPlatform()
            ->addJsFile('codemirror.min.js', 'codemirror', null, null, true, true)
            ->addCssFile('codemirror.min.css', 'codemirror', null, null, null, true)
            ->addCssFile('codemirror/theme/mdn-like.min.css', 'codemirror-theme-midnight', array('codemirror'), null, null, true);
        // Add JS to instantiate codemirror
        $js = ['var codeMirrors = [];'];
        $langs = [];
        foreach (self::$_elements[$form->settings['#id']] as $id => $lang) {
            $langs[$lang] = $lang;
            $js[] = 'codeMirrors.push(CodeMirror.fromTextArea(document.getElementById("'. $id .'-editor"), {
        lineNumbers: true,
        mode: "' . $lang . '",
        theme: "mdn-like"
    }));';
        }
        // Need to manuall call save() for codemirror instances when submitting form without using the form submit method
        $js[] = sprintf(
            '$("#%s").on("form_ajax_submit.sabai", function (e) {
    codeMirrors.forEach(function (cm) {
        cm.save();
    });
});',
            $form->settings['#id']
        );
        // Refresh codemirror instance when tab switched
        $js[] = '$("a[data-toggle=\'' . DRTS_BS_PREFIX . 'pill\']").on("shown.bs.tab", function (e) {
    var cmContainer = $(e.target.getAttribute("data-target")).find(".CodeMirror")[0];
    if (cmContainer && cmContainer.CodeMirror && !cmContainer.codeMirrorRefreshed) {
        cmContainer.CodeMirror.refresh();
        cmContainer.codeMirrorRefreshed = true;
    }
});';
        //  Add CodeMirror modes
        foreach ($langs as $lang) {
            $this->_application->getPlatform()
                ->addJsFile('codemirror/mode/' . $lang . '.min.js', 'codemirror-mode-' . $lang, array('codemirror'), null, true, true);
        }
        // Add js
        $form->settings['#js_ready'][] = implode(PHP_EOL, $js);
    }
}
