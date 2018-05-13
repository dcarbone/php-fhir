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
use DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum;
use DCarbone\PHPFHIR\ClassGenerator\Enum\SimpleClassTypesEnum;

/**
 * Class NSUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class NSUtils {
    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param string|null                             $classNS
     * @return string
     */
    public static function generateRootNamespace(Config $config, $classNS) {
        $outputNS = (string)$config->getOutputNamespace();
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

    /**
     * @param SimpleClassTypesEnum $type
     * @return string
     */
    public static function getSimpleTypeNamespace(SimpleClassTypesEnum $type) {
        switch ((string)$type) {
            case SimpleClassTypesEnum::_LIST:
                return 'FHIRList';
            case SimpleClassTypesEnum::PRIMITIVE:
                return 'FHIRPrimitive';

            default:
                return '';
        }
    }

    /**
     * @param string                     $name
     * @param ComplexClassTypesEnum|null $type
     * @return string
     */
    public static function getComplexTypeNamespace($name, ComplexClassTypesEnum $type = null) {
        switch ((string)$type) {
            case ComplexClassTypesEnum::DOMAIN_RESOURCE:
                return 'FHIRDomainResource';

            case ComplexClassTypesEnum::RESOURCE:
                return 'FHIRResource';

            case ComplexClassTypesEnum::ELEMENT:
                return 'FHIRElement';

            case ComplexClassTypesEnum::QUANTITY:
                return 'FHIRElement\\FHIRQuantity';

            case ComplexClassTypesEnum::COMPONENT:
                return sprintf('FHIRResource\\FHIR%s', strstr($name, '.', true));

            default:
                return '';
        }
    }
}