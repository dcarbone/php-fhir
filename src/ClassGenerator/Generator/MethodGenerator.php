<?php namespace DCarbone\PHPFHIR\ClassGenerator\Generator;

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
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Utilities\NSUtils;

/**
 * Class MethodGenerator
 * @package DCarbone\PHPFHIR\Generator
 */
abstract class MethodGenerator
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $class
     */
    public static function implementXMLSerialize(Config $config, ClassTemplate $class)
    {
        $method = new BaseMethodTemplate($config, 'xmlSerialize');
        $method->addParameter(new BaseParameterTemplate($config, 'returnSXE', 'boolean', 'false'));
        $method->addParameter(new BaseParameterTemplate($config, 'sxe', '\\SimpleXMLElement', 'null'));
        $method->setReturnStatement('$sxe->saveXML()');
        $method->setReturnValueType('string|\\SimpleXMLElement');
        $class->addMethod($method);

        $properties = $class->getProperties();

        $simple = true;
        if (2 === count($properties)) {
            foreach ($properties as $property) {
                $name = $property->getName();

                if ('_fhirElementName' === $name || 'value' === $name) {
                    continue;
                }

                $simple = false;
                break;
            }
        } else {
            $simple = false;
        }

        $rootElementName = str_replace(
            NameUtils::$classNameSearch,
            NameUtils::$classNameReplace,
            $class->getElementName()
        );
        // If this is the root object...
        $method->addLineToBody(sprintf(
            'if (null === $sxe) $sxe = new \\SimpleXMLElement(\'<%s xmlns="%s"></%s>\');',
            $rootElementName,
            FHIR_XMLNS,
            $rootElementName
        ));

        // For simple properties we need to simply add an attribute.
        if ($simple) {
            $method->addLineToBody('$sxe->addAttribute(\'value\', $this->value);');
        } else {
            if ('ResourceContainer' === $rootElementName || 'Resource.Inline' === $rootElementName) {
                $first = true;
                foreach ($properties as $property) {
                    $name = $property->getName();
                    if ('_fhirElementName' === $name) {
                        continue;
                    }

                    if ($first) {
                        $method->addLineToBody(sprintf(
                            'if (isset($this->%s)) $this->%s->xmlSerialize(true, $sxe->addChild(\'%s\'));',
                            $name,
                            $name,
                            $name
                        ));
                        $first = false;
                    } else {
                        $method->addLineToBody(sprintf(
                            'else if (isset($this->%s)) $this->%s->xmlSerialize(true, $sxe->addChild(\'%s\'));',
                            $name,
                            $name,
                            $name
                        ));
                    }
                }
            } else {
                // Determine if this class is a child...
                if ($class->getExtendedElementMapEntry()) {
                    $method->addLineToBody('parent::xmlSerialize(true, $sxe);');
                }

                foreach ($properties as $property) {
                    $name = $property->getName();

                    if ('_fhirElementName' === $name) {
                        continue;
                    }

                    if ($config->getXmlSerializationAttributeOverride($rootElementName, $name)) {
                        $method->addLineToBody(
                            sprintf(
                                'if (isset($this->%s)) $sxe->addAttribute(\'%s\', (string)$this->%s);',
                                $name,
                                $name,
                                $name
                            )
                        );

                        continue;
                    }

                    if ($property->isCollection()) {
                        $method->addLineToBody(sprintf(
                            'if (0 < count($this->%s)) {',
                            $name
                        ));
                        $method->addLineToBody(sprintf(
                            '    foreach($this->%s as $%s) {',
                            $name,
                            $name
                        ));
                        $method->addLineToBody(sprintf(
                            '        $%s->xmlSerialize(true, $sxe->addChild(\'%s\'));',
                            $name,
                            $name
                        ));
                        $method->addLineToBody('    }');
                        $method->addLineToBody('}');
                    } else {
                        if ($property->hasHTMLValue()) {
                            $class->addImport(NSUtils::generateRootNamespace($config, 'PHPFHIRHelper'));

                            $method->addLineToBody(sprintf(
                                'if (isset($this->%s)) {',
                                $name
                            ));
                            $method->addLineToBody(sprintf(
                                '   PHPFHIRHelper::recursiveXMLImport($sxe, $this->%s);',
                                $name
                            ));
                            $method->addLineToBody('}');
                        } else {
                            if ($property->isPrimitive() || $property->isList()) {
                                $method->addLineToBody(sprintf(
                                    'if (isset($this->%s)) {',
                                    $name
                                ));
                                $method->addLineToBody(sprintf(
                                    '    $%sElement = $sxe->addChild(\'%s\');',
                                    $name,
                                    $name
                                ));
                                $method->addLineToBody(sprintf(
                                    '    $%sElement->addAttribute(\'value\', (string)$this->%s);',
                                    $name,
                                    $name
                                ));
                                $method->addLineToBody('}');
                            } else {
                                $method->addLineToBody(sprintf(
                                    'if (isset($this->%s)) $this->%s->xmlSerialize(true, $sxe->addChild(\'%s\'));',
                                    $name,
                                    $name,
                                    $name
                                ));
                            }
                        }
                    }
                }
            }
        }

        $method->addLineToBody('if ($returnSXE) return $sxe;');
    }
}
