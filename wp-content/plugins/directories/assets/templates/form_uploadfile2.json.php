<?php
if (isset($error)) {
    echo $this->JsonEncode(['error' => $this->H($error)]);
    return;
}
$file_arr = ['success' => 1];
if (!empty($files)) {
    foreach ($files as $file) {
        $file['icon'] = $this->FileIcon($file['extension']);
        $file_arr['files'][] = array_map([$this, 'H'], $file);
    }
}
echo $this->JsonEncode($file_arr);