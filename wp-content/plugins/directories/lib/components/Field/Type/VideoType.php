<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class VideoType extends AbstractType implements
    IHumanReadable,
    IVideo,
    IQueryable,
    ICopiable,
    IConditionable
{
    use ConditionableDefaultTrait;

    protected static $_videoData = [];
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Video', 'directories'),
            'default_settings' => [],
            'icon' => 'fas fa-video',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'id' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'was' => 'id',
                    'length' => 20,
                ),
                'provider' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 20,
                    'notnull' => true,
                    'was' => 'provider',
                ),
                'thumbnail_url' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'thumbnail_url',
                ),
                'title' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'title',
                ),
            ),
            'indexes' => array(
                'id' => array(
                    'fields' => array('id' => array('sorting' => 'ascending')),
                    'was' => 'id',
                ),
            ),
        );
    }
    
    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        foreach (array_keys($values) as $i) {
            if (!is_array($values[$i])
                || !is_string($values[$i]['id'])
                || strlen($values[$i]['id']) === 0
                || empty($values[$i]['provider'])
            ) {
                unset($values[$i]);
                continue;
            }

            switch ($values[$i]['provider']) {
                case 'youtube':
                case 'vimeo':
                    if (strpos($values[$i]['id'], 'http') === 0) {
                        if (!$values[$i]['id'] = self::getVideoIdFromUrl($values[$i]['provider'], [$values[$i]['id']])) {
                            unset($values[$i]);
                            continue 2;
                        }
                    }
                    break;
                default:
                    continue 2;
            }

            if (empty($values[$i]['thumbnail_url'])
                || empty($values[$i]['title'])
            ) {
                try {
                    $video = $this->_getVideoData($values[$i]['provider'], $values[$i]['id']);
                    $values[$i]['thumbnail_url'] = $video['thumbnail_url'];
                    $values[$i]['title'] = $video['title'];
                } catch (\Exception $e) {
                    $this->_application->LogError($e->getMessage());
                }
            }
        }

        return array_values($values);
    }

    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        foreach (array_keys($currentLoadedValue) as $key) {
            $currentLoadedValue[$key] = array_filter($currentLoadedValue[$key]);
            unset($currentLoadedValue[$key]['title'], $currentLoadedValue[$key]['thumbnail_url']);
        }
        if (count($currentLoadedValue) !== count($valueToSave)) return true;

        foreach (array_keys($currentLoadedValue) as $key) {
            if (count($currentLoadedValue[$key]) !== count($valueToSave[$key])
                || array_diff_assoc($currentLoadedValue[$key], $valueToSave[$key])
            ) return true;
        }
        return false;
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $value) {
            switch ($value['provider']) {
                case 'youtube':
                    $ret[] = 'https://youtu.be/' . $value['id'];
                    break;
                case 'vimeo':
                    $ret[] = 'https://vimeo.com/' . $value['id'];
                    break;
            }
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
    
    protected function _getVideoData($provider, $id)
    {
        if (isset(self::$_videoData[$provider][$id])) return self::$_videoData[$provider][$id];
        
        switch ($provider) {
            case 'youtube':
                $url = 'https://www.youtube.com/oembed?url=' . rawurlencode('http://www.youtube.com/watch?v=' . $id) . '&format=json';
                break;
            case 'vimeo':
                $url = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode('http://vimeo.com/' . $id);
                break;
            default:
                throw new Exception\RuntimeException('Invalid video provider.');
        }
        
        $result = $this->_application->getPlatform()->remoteGet($url);
        if (!$result = json_decode($result, true)) {
            throw new Exception\RuntimeException('Failed decoding video JSON return from URL: ' . $url);
        }
        self::$_videoData[$provider][$id] = $result;
        
        return self::$_videoData[$provider][$id];
    }

    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return array(
            'example' => __('1 or 0', 'directories'),
            'tip' => __('Enter 1 for items with a video, 0 for items without any video.', 'directories'),
        );
    }

    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        if ((bool)$paramStr) {
            $query->fieldIsNotNull($fieldName, 'id');
        } else {
            $query->fieldIsNull($fieldName, 'id');
        }
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }

    public static function getVideoIdFromUrl($provider, $url)
    {
        switch ($provider){
            case 'youtube':
                $pattern = '~(?#!js YouTubeId Rev:20160125_1800)
                    # Match non-linked youtube URL in the wild. (Rev:20130823)
                    https?://          # Required scheme. Either http or https.
                    (?:[0-9A-Z-]+\.)?  # Optional subdomain.
                    (?:                # Group host alternatives.
                      youtu\.be/       # Either youtu.be,
                    | youtube          # or youtube.com or
                      (?:-nocookie)?   # youtube-nocookie.com
                      \.com            # followed by
                      \S*?             # Allow anything up to VIDEO_ID,
                      [^\w\s-]         # but char before ID is non-ID char.
                    )                  # End host alternatives.
                    ([\w-]{11})        # $1: VIDEO_ID is exactly 11 chars.
                    (?=[^\w-]|$)       # Assert next char is non-ID or EOS.
                    (?!                # Assert URL is not pre-linked.
                      [?=&+%\w.-]*     # Allow URL (query) remainder.
                      (?:              # Group pre-linked alternatives.
                        [\'"][^<>]*>   # Either inside a start tag,
                      | </a>           # or inside <a> element text contents.
                      )                # End recognized pre-linked alts.
                    )                  # End negative lookahead assertion.
                    [?=&+%\w.-]*       # Consume any URL (query) remainder.
                    ~ix';

                return preg_match($pattern, $url, $matches) ? $matches[1] : false;

            case 'vimeo':
                $pattern = '/(http|https)?:\/\/(www\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|)(\d+)(?:|\/\?)/';

                return preg_match($pattern, $url, $matches) ? $matches[4] : false;

            default:
                return false;
        }
    }
}