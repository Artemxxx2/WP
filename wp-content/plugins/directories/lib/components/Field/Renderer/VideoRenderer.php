<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class VideoRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'columns' => 1,
                'privacy_mode' => false,
            ),
            'separatable' => false,
        );
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return array(
            'columns' => array(
                '#title' => __('Number of columns', 'directories'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 4,
                '#integer' => true,
                '#default_value' => $settings['columns'],
            ),
            'privacy_mode' => [
                '#title' => __('Embed videos in privacy mode', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['privacy_mode'])
            ],
        );
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $width = 12 / $settings['columns'];
        if ($width === 12) {
            $ret = [];
            foreach ($values as $value) {
                $ret[] = '<div class="drts-field-video">';
                switch ($value['provider']) {
                    case 'vimeo':
                        $ret[] = $this->_renderVimeoVideo($field, $settings, $value);
                        break;
                    default:
                        $ret[] = $this->_renderYouTubeVideo($field, $settings, $value);
                }
                $ret[] = '</div>';
            }
        } else {
            $ret = array('<div class="drts-row">');
            foreach ($values as $value) {
                $ret[] = '<div class="drts-col-md-' . $width . '"><div class="drts-field-video fitvidsignore">';
                switch ($value['provider']) {
                    case 'vimeo':
                        $ret[] = $this->_renderVimeoVideo($field, $settings, $value);
                        break;
                    default:
                        $ret[] = $this->_renderYouTubeVideo($field, $settings, $value);
                }
                $ret[] = '</div></div>';
            }
            $ret[] = '</div>';
        }

        return implode(PHP_EOL, $ret);
    }
    
    protected function _renderVimeoVideo(IField $field, array $settings, array $value)
    {
        return sprintf('
            <iframe src="//player.vimeo.com/video/%s?api=1&byline=0&portrait=1&title=1&background=0&mute=0&loop=0&autoplay=0%s" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>',
            $this->_application->H($value['id']),
            empty($settings['privacy_mode']) ? '' : '&dnt=1'
        );
    }
    
    protected function _renderYoutubeVideo(IField $field, array $settings, array $value)
    {
        return sprintf('
            <iframe src="//www.%1$s/embed/%2$s?enablejsapi=1&controls=1&fs=1&iv_load_policy=3&rel=0&showinfo=1&loop=0&start=0&playlist=%2$s" frameborder="0" allowfullscreen></iframe>',
            empty($settings['privacy_mode']) ? 'youtube.com' : 'youtube-nocookie.com',
            $this->_application->H($value['id'])
        );
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        return [
            'columns' => [
                'label' => __('Number of columns', 'directories'),
                'value' => $settings['columns'],
            ],
        ];
    }
}