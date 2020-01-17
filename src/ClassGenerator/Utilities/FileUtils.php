<?php namespace DCarbone\PHPFHIR\ClassGenerator\Utilities;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\ClassGenerator\Config;

/**
 * Class FileUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class FileUtils
{
    /**
     * @param string $namespace
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     */
    public static function createDirsFromNS($namespace, Config $config)
    {
        if ('\\' === $namespace) {
            $config->getLogger()->debug('Skipping dir creation for root namespace.');
            return;
        }

        $path = rtrim(trim($config->getOutputPath()), "/\\");
        foreach (explode('\\', $namespace) as $dirName) {
            $path = sprintf('%s/%s', $path, $dirName);
            if (is_dir($path)) {
                $config->getLogger()->debug(sprintf('Directory at path "%s" already exists.', $path));
            } else {
                $config->getLogger()->info(sprintf('Attempting to create directory at path "%s"...', $path));
                if (!(bool)mkdir($path)) {
                    $msg = 'Unable to create directory at path "' . $path . '"';
                    $config->getLogger()->critical($msg);
                    throw new \RuntimeException($msg);
                }
            }
        }
    }

    /**
     * @param string $namespace
     * @return string
     */
    public static function buildDirPathFromNS($namespace)
    {
        return preg_replace(['{[\\\]}S', '{[/]{2,}}S'], '/', trim($namespace, "\\"));
    }
}
