<?php namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;

/**
 * Class NSUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class NSUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param string|null $classNS
     * @return string
     */
    public static function generateRootNamespace(VersionConfig $config, $classNS)
    {
        $outputNS = (string)$config->getNamespace();
        $classNS = (string)$classNS;

        if ('' === $outputNS && '' === $classNS) {
            return '';
        }

        if ('' === $outputNS) {
            return $classNS;
        }

        if ('' === $classNS) {
            return $outputNS;
        }

        return sprintf('%s\\%s', $outputNS, $classNS);
    }

}