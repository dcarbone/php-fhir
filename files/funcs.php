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
 * @param string $requiredFile
 * @param array $vars
 * @return mixed
 */
function require_with(string $requiredFile, array $vars): mixed
{
    $num = extract($vars, EXTR_OVERWRITE);
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
    unset($vars, $num);
    return require $requiredFile;
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
        // TODO: if it works, is it really a shit idea?  Probably.
        if ('libxmlOptMask' === $k) {
            $k = 'libxmlOpts';
            $literal = true;
        } else if ('xhtmlLibxmlOptMask' === $k) {
            $k = 'xhtmlLibxmlOpts';
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

/**
 * Sloppy little function to ensure filenames are consistent or whatever
 *
 * @param string $bit
 * @return string
 */
function classFilenameFormat(string $bit): string
{
    return match ($bit) {
        'api' => 'API',
        'fhir' => 'FHIR',
        'xhtml' => 'XHTML',
        'xml' => 'XML',
        'http' => 'HTTP',
        'curl' => 'CURL',
        default => ucfirst($bit),
    };
}
