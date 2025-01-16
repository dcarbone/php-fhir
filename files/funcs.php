<?php declare(strict_types=1);

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * require_with is used to ensure a clean context per required template file.
 *
 * @param string $_filename
 * @param array $vars
 * @return mixed
 */
function require_with(string $_filename, array $vars): mixed
{
    if (array_key_exists('_filename', $vars)) {
        throw new \InvalidArgumentException('Cannot set "_filename" as key in $vars array.');
    } else if (array_key_exists('_realpath', $vars)) {
        throw new \InvalidArgumentException('Cannot set "_realpath" as key in $vars array.');
    }
    $_realpath = realpath($_filename);
    if (false === $_realpath) {
        throw new \RuntimeException(sprintf('Unable to resolve path for file "%s"', $_filename));
    }
    $num = extract($vars);
    if ($num !== count($vars)) {
        throw new \RuntimeException(
            sprintf(
                'Expected "%d" variables to be extracted but only "%d" were successful.  Keys: ["%s"]',
                count($vars),
                $num,
                implode('", "', array_keys($vars))
            )
        );
    }
    // unset vars defined by this func
    unset($vars, $num, $_filename);
    return require $_realpath;
}

/**
 * Makes array var exporting less terrible.
 *
 * @param mixed $var root var
 * @param int $indent current indent level, only used during array exporting
 * @param bool $indentFirst if true, indents the first line of the array
 * @param int $indentSize number of spaces to output per indent level
 * @return string
 */
function pretty_var_export(mixed $var, int $indent = 0, bool $indentFirst = false, int $indentSize = 4): string
{
    if (!is_array($var)) {
        return var_export($var, true);
    }

    if ([] === $var) {
        return '[]';
    }

    $out = sprintf("%s[", str_repeat(' ', $indentFirst ? $indent * $indentSize : 0));
    foreach ($var as $k => $v) {
        $literal = false;
        $indentFirst = is_int($k);
        // handle output of option mask constant names.
        if (is_string($k) && str_ends_with($k, 'OptMask')) {
            $k = str_replace('OptMask', 'Opts', $k);
            $literal = true;
        }
        $out = sprintf("%s\n%s%s => %s,",
            $out,
            str_repeat(' ', ($indent + 1) * $indentSize),
            var_export($k, true),
            $literal ? $v : pretty_var_export($v, $indent + 1, $indentFirst, $indentSize),
        );
    }
    return sprintf("%s\n%s]", $out, str_repeat(' ', $indent * $indentSize));
}
