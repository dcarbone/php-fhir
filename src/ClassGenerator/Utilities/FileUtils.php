<?php namespace DCarbone\PHPFHIR\ClassGenerator\Utilities;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Class FileUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class FileUtils
{
    /**
     * @param string $outputPath
     * @param string $namespace
     */
    public static function createDirsFromNS($outputPath, $namespace)
    {
        if ('\\' === $namespace)
            return;

        $path = rtrim(trim($outputPath), "/\\");
        foreach(explode('\\', $namespace) as $dirName)
        {
            $path = sprintf('%s/%s', $path, $dirName);
            if (!is_dir($path))
            {
                $made = (bool)mkdir($path);
                if (false === $made)
                    throw new \RuntimeException('Unable to create directory at path "'.$path.'"');
            }
        }
    }

    /**
     * @param string $namespace
     * @return string
     */
    public static function buildDirPathFromNS($namespace)
    {
        return preg_replace(array('{[\\\]}S', '{[/]{2,}}S'), '/', trim($namespace, "\\"));
    }
}
