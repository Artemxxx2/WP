<?php
$arr = [];
if (!empty($identities)) {
    foreach ($identities as $identity) {
        $arr[] = array(
            'id' => $identity->id,
            'title' => $this->H($identity->name),
            'username' => $identity->username,
            'gravatar' => $this->GravatarUrl($identity->email, 24),
        );
    }
}
echo $this->JsonEncode($arr);