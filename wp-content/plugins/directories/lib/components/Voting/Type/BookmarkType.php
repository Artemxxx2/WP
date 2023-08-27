<?php
namespace SabaiApps\Directories\Component\Voting\Type;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Link;
use SabaiApps\Directories\Request;

class BookmarkType extends AbstractType
{
    protected function _votingTypeInfo()
    {
        return [
            'label' => __('Bookmarks', 'directories'),
            'label_button' => __('Bookmark button', 'directories'),
            'label_action' => _x('Bookmark', 'action', 'directories'),
            'label_unaction' => _x('Unbookmark', 'action', 'directories'),
            'label_statistic' => __('Bookmark count', 'directories'),
            'icon' => 'far fa-heart',
            'icon_active' => 'fas fa-heart',
            'min' => 1,
            'max' => 1,
            'step' => 1,
            'allow_empty' => false,
            'allow_anonymous' => true,
            'anonymous_use_cookie' => true,
            'require_permission' => true,
            'require_down_permission' => false,
            'require_own_permission' => false,
            'permission_label' => __('Bookmark %s', 'directories'),
            'entity_button' => true,
            'entity_statistic' => true,
            'table_headers' => [
                'author' => __('User', 'directories'),
                'created' => __('Date', 'directories'),
            ],
        ];
    }

    public function votingTypeButtonSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            'view_container' => [
                '#type' => 'textfield',
                '#title' => __('View HTML ID', 'drts'),
                '#description' => __('Enter the HTML ID of the view that should be updated after clicking the button.', 'directories'),
                '#default_value' => isset($settings['view_container']) ? $settings['view_container'] : null,
                '#horizontal' => true,
                '#weight' => 5,
                '#field_prefix' => '#',
            ],
        ];
    }

    public function votingTypeOnDisplayButtonLink(Link $link, IEntity $entity, array $settings, $displayName)
    {
        if (empty($settings['view_container'])) return;

        $link->setAttribute('data-view-container', $settings['view_container']);
        $this->_application->getPlatform()->addJsFile('voting-bookmark.min.js', 'drts-voting-bookmark', ['drts-voting'], 'directories');
    }

    public function votingTypeOnVoteEntity(IEntity $entity, array &$response, Request $request)
    {
        if ((!$view_name = $request->asStr('view_name'))
            || (!$view_container = $request->asStr('view_container'))
        ) return;

        $response['view_html'] = $this->_application->getPlatform()->render(
            $this->_application->Entity_Bundle($entity)->getPath(),
            ['settings' => ['load_view' => $view_name]],
            ['wrap' => false, 'container' => '#' . $view_container]
        );
    }

    public function votingTypeFormat(array $value, $format = null)
    {
        switch ($format) {
            case 'num':
            case 'column':
                return (int)$value['count'];
            default:
                return $this->_application->H(_n('%d bookmark', '%d bookmarks', $value['count'], 'directories'));
        }
    }
}