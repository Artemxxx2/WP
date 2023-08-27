<?php
if (empty($settings['other']['not_found']['custom'])) {
    $not_found_html = '<p>' . $this->H(__('Sorry, there were no items that matched your criteria.', 'directories')) . '</p>';
} else {
    if (!strlen($settings['other']['not_found']['html'])) return;

    $not_found_html = $this->Htmlize($settings['other']['not_found']['html']);
}
if (strpos($not_found_html, '%') !== false) {
    $current_user = $this->UserIdentity();
    $replacements = [
        '%current_user_id%' => $current_user->id,
        '%current_user_name%' => $current_user->username,
        '%current_user_display_name%' => $current_user->name,
    ];
    $not_found_html = strtr($not_found_html, $this->Filter('view_not_found_html_tokens_replace', $replacements));
}
?>
<div class="drts-view-entities-none-found">
    <?php echo $not_found_html;?>
</div>