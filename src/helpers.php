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

/**
 * Checks if a command exist on a typical Linux system
 * @param string $commands
 * @return bool
 */
function command_exists(string ...$commands): bool
{
    $commands_exist = true;
    foreach ($commands as $command) {
        if ((null === shell_exec("command -v $command_name")) ? false : true) {
            continue;
        }

        $commands_exist = false;
        break;
    }

    return $commands_exist;
}

/**
 * Check if shell_exec is enabled on the current server
 */
function exec_enabled()
{
    return function_exists('shell_exec')
        && is_callable('shell_exec')
        && !in_array('shell_exec', array_map('trim', explode(', ', ini_get('disable_functions'))))
        && strtolower(ini_get('safe_mode')) != 1;
}
