<?php

namespace DCarbone\PHPFHIR\Definition;

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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Utilities\XMLUtils;

/**
 * Class TypeRelator
 * @package DCarbone\PHPFHIR
 */
abstract class TypeRelationshipBuilder
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \SimpleXMLElement $element
     */
    public static function determineParent(Config $config, Types $types, Type $type, \SimpleXMLElement $element) {
        $fhirName = XMLUtils::getBaseFHIRElementNameFromExtension($element);
        if (null === $fhirName) {
            $fhirName = XMLUtils::getBaseFHIRElementNameFromRestriction($element);
        }
        if (null !== $fhirName) {
            if (0 === strpos($fhirName, 'xs')) {
                $fhirName = substr($fhirName, 3);
            }
            if ($ptype = $types->getTypeByFHIRName($fhirName)) {
                $type->setParentType($ptype);
                $config->getLogger()->info(sprintf(
                    'Type %s has parent %s',
                    $type,
                    $ptype
                ));
                return;
            }
        }
        $config->getLogger()->info(sprintf(
            'Unable to locate parent of type %s',
            $type
        ));
    }

    public static function findPropertyType(Config $config, Types $types, Type $type) {

    }
}