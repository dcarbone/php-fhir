<?php

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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
function require_with(string $requiredFile, array $vars)
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
    if (!isset($config) || !($config instanceof \DCarbone\PHPFHIR\Config\VersionConfig)) {
        throw new \LogicException(sprintf(
            'Refusing to require "%s" as you didn\'t provide \'config\' => $config(%s)',
            $requiredFile,
            \DCarbone\PHPFHIR\Config\VersionConfig::class,
        ));
    }
    // unset vars defined by this func
    unset($vars, $num);
    return require $requiredFile;
}
