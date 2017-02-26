<?php namespace DCarbone\PHPFHIR\ClassGenerator\Utilities;

/*
 * Copyright 2016-2017 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Logger;

/**
 * Class FileUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class FileUtils
{
    /**
     * @param string $outputPath
     * @param string $namespace
     * @param Logger $logger
     */
    public static function createDirsFromNS($outputPath, $namespace, Logger $logger)
    {
        if ('\\' === $namespace)
        {
            $logger->debug('Skipping dir creation for root namespace.');
            return;
        }

        $path = rtrim(trim($outputPath), "/\\");
        foreach(explode('\\', $namespace) as $dirName)
        {
            $path = sprintf('%s/%s', $path, $dirName);
            if (is_dir($path))
            {
                $logger->debug(sprintf('Directory at path "%s" already exists.', $path));
            }
            else
            {
                $logger->info(sprintf('Attempting to create directory at path "%s"...', $path));
                $made = (bool)mkdir($path);
                if (false === $made)
                {
                    $msg = 'Unable to create directory at path "'.$path.'"';
                    $logger->critical($msg);
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
