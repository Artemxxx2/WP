<?php
namespace SabaiApps\Directories\Component\WordPressContent\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class QueryPosts extends Controller
{
    protected function _doExecute(Context $context)
    {
        $list = [];
        if (($post_type = $context->getRequest()->asStr('post_type'))
            && ($q = trim($context->getRequest()->asStr('query')))
        ) {
            $num = $context->getRequest()->asInt('num', 5);
            if ($num > 20) $num = 20;
            $args = [
                'post_type' => $post_type,
                's' => $q,
                'posts_per_page' => $num,
            ];
            if ($user_id = $context->getRequest()->asInt('user_id')) {
                $args['author'] = $user_id;
            }
            foreach (get_posts($args) as $post) {
                if (!$url = get_permalink($post)) continue;

                $list[] = [
                    'id' => $post->ID,
                    'slug' => $post->post_slug,
                    'title' => $post->post_title,
                    'url' => $url,
                ];
            }
        }
        $context->addTemplate('system_list')->setAttributes(['list' => $list]);
    }
}
