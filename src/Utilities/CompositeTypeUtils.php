<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Composite\CompositeType;
use DCarbone\PHPFHIR\Composite\CompositeTypes;
use DCarbone\PHPFHIR\Config;

class CompositeTypeUtils
{
    public static function buildCompositeTypes(Config $config): CompositeTypes
    {
        $types = new CompositeTypes();
        foreach($config->getVersionsIterator() as $version) {
            $type = $version->getDefinition()->getTypes()->getTypeByName('Patient');
            $types->addType($version, $type);
        }
        return $types;
    }
}