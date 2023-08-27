<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class SliceArrayHelper
{
    public function help(Application $application, array $arr, $columnCount, $vertical = true)
    {
        if ($columnCount < 1
            || (!$entity_count = count($arr))
        ) return [];

        $ret = [];
        if ($vertical) {
            $additional_entity_count = $entity_count % $columnCount;
            $entity_count_per_slice = floor($entity_count / $columnCount);
            $last_slice_index = $columnCount - 1;
            $sliced_count = 0;
            for ($i = 0; $i <= $last_slice_index; $i++) {
                if ($additional_entity_count) {
                    --$additional_entity_count;
                    $slice_entity_count = $entity_count_per_slice + 1;
                } else {
                    $slice_entity_count = $entity_count_per_slice;
                }
                $slice = array_slice($arr, $sliced_count, $slice_entity_count, true);
                $ret[] = $slice;
                $sliced_count += $slice_entity_count;
            }
        } else {
            $entity_count_per_slice = $columnCount;
            $last_slice_index = ceil($entity_count / $columnCount) - 1;
            for ($i = 0; $i <= $last_slice_index; $i++) {
                $ret[$i] = array_slice($arr, $i * $entity_count_per_slice, $i === $last_slice_index ? null : $entity_count_per_slice, true);
            }
        }
        return $ret;
    }
}