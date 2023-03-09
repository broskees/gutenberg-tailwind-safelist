<?php

namespace Broskees\GutenbergTwSafelist;

function array_map_recursive($callback, array $input): array
{
    $output = [];

    foreach ($input as $key => $data) {
        if (is_array($data)) {
            $output[$key] = call_user_func_array(
                __FUNCTION__,
                [
                    $callback,
                    $callback($data)
                ]
            );
        } else {
            $output[$key] = $callback($data);
        }
    }

    return $output;
}
