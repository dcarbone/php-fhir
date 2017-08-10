<?php namespace DCarbone\PHPFHIR\ClassGenerator\Generator;

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

use DCarbone\PHPFHIR\ClassGenerator\Config;
use DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum;
use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\GetterMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\SetterMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\BaseParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\PropertyParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NSUtils;

/**
 * Class MethodGenerator
 * @package DCarbone\PHPFHIR\ClassGenerator\Generator
 */
abstract class MethodGenerator {

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function implementConstructor(Config $config, ClassTemplate $classTemplate) {
        // TODO: I don't like any of what I have here.  Do better.
//        if ($classTemplate->hasProperty('value')) {
//            $method = new BaseMethodTemplate($config, '__construct');
//            $param = new BaseParameterTemplate($config, 'data', 'mixed', '[]');
//
//        }
//        $method->addParameter($param);
//        if ($classTemplate->getXSDMapEntry()->getExtendedMapEntry()) {
//            $method->addLineToBody('parent::__construct($data);');
//        }
//        foreach($classTemplate->getProperties() as $property) {
//            $method->addLineToBody('if (isset($data[\''.$property->getName().'\'])) {');
//            $method->addLineToBody('    $this->'.$property->getName().' = $data[\''.$property->getName().'\'];');
//            $method->addLineToBody('}');
//        }
//        $classTemplate->addMethod($method);
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $propertyTemplate
     */
    public static function implementMethodsForProperty(Config $config, ClassTemplate $classTemplate, BasePropertyTemplate $propertyTemplate) {
        if ($propertyTemplate->requiresGetter()) {
            if ($propertyTemplate->getFHIRElementType() === 'ResourceContainer') {
                $method = new BaseMethodTemplate($config, sprintf('get%s', NameUtils::getPropertyMethodName($propertyTemplate->getName())));
                $method->setDocumentation($propertyTemplate->getDocumentation());
                if ($propertyTemplate->isCollection()) {
                    $classTemplate->addImport(NSUtils::generateRootNamespace($config, 'FHIRResourceContainer'));
                    $method->addLineToBody("if (count(\$this->{$propertyTemplate->getName()}) > 0) { ");
                    $method->addLineToBody('    $resources = [];');
                    $method->addLineToBody("    foreach(\$this->{$propertyTemplate->getName()} as \$container) {");
                    $method->addLineToBody('        if ($container instanceof FHIRResourceContainer) {');
                    $method->addLineToBody('            $resources[] = $container->jsonSerialize();');
                    $method->addLineToBody('        }');
                    $method->addLineToBody('    }');
                    $method->addLineToBody('    return $resources;');
                    $method->addLineToBody('}');
                    $method->addLineToBody('return [];');
                    $method->setReturnValueType('array');
                } else {
                    $method->addLineToBody("return isset(\$this->{$propertyTemplate->getName()}) ? \$this->{$propertyTemplate->getName()}->jsonSerialize() : null;");
                    $method->setReturnValueType('mixed');
                }
                $classTemplate->addMethod($method);
            } else {
                $classTemplate->addMethod(self::createGetter($config, $propertyTemplate));
            }
        }

        if ($propertyTemplate->requireSetter()) {
            $classTemplate->addMethod(self::createSetter($config, $propertyTemplate));
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $propertyTemplate
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\GetterMethodTemplate
     */
    public static function createGetter(Config $config, BasePropertyTemplate $propertyTemplate) {
        $getterTemplate = new GetterMethodTemplate($config, $propertyTemplate);
        $getterTemplate->addLineToBody(sprintf('return $this->%s;', $propertyTemplate->getName()));
        return $getterTemplate;
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $propertyTemplate
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\SetterMethodTemplate
     */
    public static function createSetter(Config $config, BasePropertyTemplate $propertyTemplate) {
        $paramTemplate = new PropertyParameterTemplate($config, $propertyTemplate);

        if ($propertyTemplate->isCollection()) {
            $methodBody = sprintf(
                '$this->%s[] = %s;',
                $propertyTemplate->getName(),
                NameUtils::getPropertyVariableName($paramTemplate->getProperty()->getName())
            );
        } else {
            $methodBody = sprintf(
                '$this->%s = %s;',
                $propertyTemplate->getName(),
                NameUtils::getPropertyVariableName($paramTemplate->getProperty()->getName())
            );
        }

        $setterTemplate = new SetterMethodTemplate($config, $propertyTemplate);
        $setterTemplate->addParameter($paramTemplate);
        $setterTemplate->addLineToBody($methodBody);

        return $setterTemplate;
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function implementToString(Config $config, ClassTemplate $classTemplate) {
        $method = new BaseMethodTemplate($config, '__toString');
        $classTemplate->addMethod($method);
        $method->setReturnValueType('string');

        if ($classTemplate->hasProperty('value')) {
            $method->setReturnStatement('(string)$this->getValue()');
        } else {
            if ($classTemplate->hasProperty('id')) {
                $method->setReturnStatement('(string)$this->getId()');
            } else {
                $method->setReturnStatement('$this->get_fhirElementName()');
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function implementJsonSerialize(Config $config, ClassTemplate $classTemplate) {
        $method = new BaseMethodTemplate($config, 'jsonSerialize');
        $classTemplate->addMethod($method);

        $properties = $classTemplate->getProperties();

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

        $elementName = $classTemplate->getElementName();

        $method->setReturnValueType('mixed');
        if ($simple) {
            $method->setReturnStatement('$this->value');
        } // ResourceContainer and Resource.Inline need to just pass back the resource they contain.
        else {
            if ('ResourceContainer' === $elementName || 'Resource.Inline' === $elementName) {

                foreach ($properties as $property) {
                    $name = $property->getName();
                    if ('_fhirElementName' === $name) {
                        continue;
                    }

                    $method->addLineToBody(sprintf(
                        'if (isset($this->%s)) return $this->%s;',
                        $name,
                        $name
                    ));
                }

                // This is here just in case the ResourceContainer wasn't populated correctly for whatever reason.
                $method->setReturnStatement('null');
            } else {
                $method->setReturnValueType('array');

                // Determine if this class is a child...
                if (null === $classTemplate->getExtendedElementMapEntry()) {
                    $method->addLineToBody('$json = [];');
                } else {
                    $method->addLineToBody('$json = parent::jsonSerialize();');
                }

                // Unfortunately for the moment this value will potentially be overwritten several times during
                // JSOn generation...
                switch ((string)$classTemplate->getClassType()) {
                    case ComplexClassTypesEnum::RESOURCE:
                    case ComplexClassTypesEnum::DOMAIN_RESOURCE:
                        $method->addLineToBody('$json[\'resourceType\'] = $this->_fhirElementName;');
                        break;
                }

                foreach ($properties as $property) {
                    $name = $property->getName();

                    if ('_fhirElementName' === $name) {
                        continue;
                    }

                    if ($property->isCollection()) {
                        $method->addLineToBody(sprintf(
                            'if (0 < count($this->%s)) {',
                            $name
                        ));
                        $method->addLineToBody(sprintf(
                            '    $json[\'%s\'] = [];',
                            $name
                        ));
                        $method->addLineToBody(sprintf(
                            '    foreach($this->%s as $%s) {',
                            $name,
                            $name
                        ));
                        $method->addLineToBody(sprintf(
                            '        $json[\'%s\'][] = $%s;',
                            $name,
                            $name
                        ));
                        $method->addLineToBody('    }');
                        $method->addLineToBody('}');
                    } else {
                        if ($property->isPrimitive() || $property->isList() || $property->isHTML()) {
                            $method->addLineToBody(sprintf(
                                'if (isset($this->%s)) $json[\'%s\'] = $this->%s;',
                                $name,
                                $name,
                                $name
                            ));
                        } else {
                            $method->addLineToBody(sprintf(
                                'if (isset($this->%s)) $json[\'%s\'] = $this->%s;',
                                $name,
                                $name,
                                $name
                            ));
                        }
                    }
                }

                $method->setReturnStatement('$json');
            }
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $classTemplate
     */
    public static function implementXMLSerialize(Config $config, ClassTemplate $classTemplate) {
        $method = new BaseMethodTemplate($config, 'xmlSerialize');
        $method->addParameter(new BaseParameterTemplate($config, 'returnSXE', 'boolean', 'false'));
        $method->addParameter(new BaseParameterTemplate($config, 'sxe', '\\SimpleXMLElement', 'null'));
        $method->setReturnStatement('$sxe->saveXML()');
        $method->setReturnValueType('string|\\SimpleXMLElement');
        $classTemplate->addMethod($method);

        $properties = $classTemplate->getProperties();

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

        $rootElementName =
            str_replace(NameUtils::$classNameSearch, NameUtils::$classNameReplace, $classTemplate->getElementName());
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
                if ($classTemplate->getExtendedElementMapEntry()) {
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
                        if ($property->isHTML()) {
                            $classTemplate->addImport(NSUtils::generateRootNamespace($config, 'PHPFHIRHelper'));

                            $method->addLineToBody(sprintf(
                                'if (isset($this->%s)) {',
                                $name
                            ));
                            $method->addLineToBody(sprintf(
                                '   PHPFHIRHelper::recursiveXMLImport($sxe, $this->%1$s);',
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
