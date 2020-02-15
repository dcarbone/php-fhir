<?php

/*
 * Copyright 2016-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/**
 * @param string $_requiredFile
 * @param array $vars
 * @return mixed
 */
function require_with($_requiredFile, array $vars)
{
    $num = extract($vars, EXTR_OVERWRITE);
    if ($num !== count($vars)) {
        throw new \RuntimeException(sprintf(
            'Expected "%d" variables to be extracted but only "%d" were successful.  Keys: ["%s"]',
            count($vars),
            $num,
            implode('", "', array_keys($vars))
        ));
    }
    unset($vars, $num);
    return require $_requiredFile;
}

/**
 * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
 * @return \DCarbone\PHPFHIR\Definition\Type
 */
function build_raw_type(VersionConfig $config)
{
    $rt = new Type($config, PHPFHIR_RAW_TYPE_NAME);
    $rt->setKind(new TypeKindEnum(TypeKindEnum::RAW));
    $rt->addDocumentationFragment(PHPFHIR_RAW_TYPE_DESCRIPTION);
    return $rt;
}

/**
 * @param \DCarbone\PHPFHIR\Definition\Type $type
 */
function type_debug(Type $type)
{
    echo "\n\n\n";
    var_dump($type->getConfig()->getVersion()->getName(), $type->getFHIRName());
    echo "\n\n\n";
}