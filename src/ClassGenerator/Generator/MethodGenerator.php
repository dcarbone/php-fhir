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

use DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum;
use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\GetterMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\SetterMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\BaseParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\PropertyParameterTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Utilities\ConstructorUtils;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Utilities\NSUtils;
use DCarbone\PHPFHIR\Utilities\SetterUtils;

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
    public static function implementConstructor(Config $config, ClassTemplate $class)
    {
        $method = new BaseMethodTemplate($config, '__construct');
        $param = new BaseParameterTemplate($config, 'data', 'mixed', '[]');
        $method->addParameter($param);
        $class->addMethod($method);

        if ($class->isPrimitive()) {
            ConstructorUtils::buildPrimitiveBody($config, $class, $method);
        } elseif ('ResourceContainer' === $class->getElementName()) {
            ConstructorUtils::implementResourceContainer($config, $class, $method);
        } else {
            ConstructorUtils::buildDefaultBody($config, $class, $method);
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $class
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $property
     */
    public static function implementMethodsForProperty(Config $config,
                                                       ClassTemplate $class,
                                                       BasePropertyTemplate $property)
    {
        if ($property->requiresGetter()) {
            if ($property->getFHIRElementType() === 'ResourceContainer') {
                $method = new BaseMethodTemplate(
                    $config,
                    sprintf(
                        'get%s',
                        NameUtils::getPropertyMethodName($property->getName())
                    )
                );
                $method->setDocumentation($property->getDocumentation());
                if ($property->isCollection()) {
                    $class->addImport(NSUtils::generateRootNamespace($config, 'FHIRResourceContainer'));
                    $method->addLineToBody("if (count(\$this->{$property->getName()}) > 0) { ");
                    $method->addLineToBody('    $resources = [];');
                    $method->addLineToBody("    foreach(\$this->{$property->getName()} as \$container) {");
                    $method->addLineToBody('        if ($container instanceof FHIRResourceContainer) {');
                    $method->addLineToBody('            $resources[] = $container->jsonSerialize();');
                    $method->addLineToBody('        }');
                    $method->addLineToBody('    }');
                    $method->addLineToBody('    return $resources;');
                    $method->addLineToBody('}');
                    $method->addLineToBody('return [];');
                    $method->setReturnValueType('array');
                } else {
                    $method->addLineToBody("return isset(\$this->{$property->getName()}) ? \$this->{$property->getName()}->jsonSerialize() : null;");
                    $method->setReturnValueType('mixed');
                }
                $class->addMethod($method);
            } else {
                $class->addMethod(self::createGetter($config, $property));
            }
        }

        if ($property->requireSetter()) {
            $class->addMethod(self::createSetter($config, $class, $property));
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $property
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\GetterMethodTemplate
     */
    public static function createGetter(Config $config, BasePropertyTemplate $property)
    {
        $getterTemplate = new GetterMethodTemplate($config, $property);
        $getterTemplate->addLineToBody(sprintf('return $this->%s;', $property->getName()));
        return $getterTemplate;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param ClassTemplate $class
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate $property
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\SetterMethodTemplate
     */
    public static function createSetter(Config $config, ClassTemplate $class, BasePropertyTemplate $property)
    {
        $paramTemplate = new PropertyParameterTemplate($config, $property);
        $setterTemplate = new SetterMethodTemplate($config, $property);
        $setterTemplate->addParameter($paramTemplate);
        $paramName = NameUtils::getPropertyVariableName($paramTemplate->getProperty()->getName());

        if (!$class->isPrimitive() && $property->isPrimitive()) {
            var_dump($class->getClassName(), $property->getName());

        }

        return $setterTemplate;

        if ($property->isCollection()) {
            $setterTemplate->addLineToBody(sprintf(
                '$this->%s[] = %s;',
                $property->getName(),
                $paramName
            ));
        } elseif (!$class->isPrimitive() && $property->isPrimitive()) {
            $setterTemplate = SetterUtils::createPrimitive($config, $class, $property);
        } else {
            $setterTemplate->addLineToBody("\$this->{$property->getName()} = {$paramName};");
        }

        return $setterTemplate;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $class
     */
    public static function implementToString(Config $config, ClassTemplate $class)
    {
        $method = new BaseMethodTemplate($config, '__toString');
        $class->addMethod($method);
        $method->setReturnValueType('string');

        if ($class->hasProperty('value')) {
            $method->setReturnStatement('(string)$this->getValue()');
        } elseif ($class->hasProperty('id')) {
            $method->setReturnStatement('(string)$this->getId()');
        } else {
            $method->setReturnStatement('$this->get_fhirElementName()');
        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate $class
     */
    public static function implementJsonSerialize(Config $config, ClassTemplate $class)
    {
        $method = new BaseMethodTemplate($config, 'jsonSerialize');
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

        $elementName = $class->getElementName();

        $method->setReturnValueType('mixed');
        if ($simple) {
            $method->setReturnStatement('$this->value');
        } elseif ('ResourceContainer' === $elementName || 'Resource.Inline' === $elementName) {
            // ResourceContainer and Resource.Inline need to just pass back the resource they contain.
            foreach ($properties as $property) {
                $name = $property->getName();
                if ('_fhirElementName' === $name) {
                    continue;
                }

                $i = 0;
                $method->addLineToBody(sprintf(
                    '%sif (isset($this->%s)) return $this->%s;',
                    ($i++ > 0 ? 'else ' : ''),
                    $name,
                    $name
                ));
            }

            // This is here just in case the ResourceContainer wasn't populated correctly for whatever reason.
            $method->setReturnStatement('null');
        } else {
            $method->setReturnValueType('array');

            // Determine if this class is a child...
            if (null === $class->getExtendedElementMapEntry()) {
                $method->addLineToBody('$json = [];');
            } else {
                $method->addLineToBody('$json = parent::jsonSerialize();');
            }

            // Unfortunately for the moment this value will potentially be overwritten several times during
            // JSOn generation...
            switch ((string)$class->getClassType()) {
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
                    $method->addLineToBody(sprintf('    foreach($this->%1$s as $%1$s) {', $name));
                    $method->addLineToBody(sprintf(
                        '        if (null !== $%1$s) $json[\'%1$s\'][] = $%1$s;',
                        $name
                    ));
                    $method->addLineToBody('    }');
                    $method->addLineToBody('}');
                } elseif ($property->isPrimitive() || $property->isList() || $property->hasHTMLValue()) {
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

            $method->setReturnStatement('$json');
        }
    }

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
