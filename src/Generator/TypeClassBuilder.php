<?php namespace DCarbone\PHPFHIR\Generator;

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

use DCarbone\PHPFHIR\Builder\Methods\Constructor;
use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\JSONSerializeUtils;
use DCarbone\PHPFHIR\Utilities\MethodUtils;
use DCarbone\PHPFHIR\Utilities\PropertyUtils;
use DCarbone\PHPFHIR\Utilities\XMLSerializeUtils;
use DCarbone\PHPFHIR\Utilities\XMLUnserializeUtils;

/**
 * Class TypeClassBuilder
 * @package DCarbone\PHPFHIR\Generator
 */
abstract class TypeClassBuilder
{
    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildPrimitiveContainerTypeClass(VersionConfig $config, Types $types, Type $type)
    {
        $out = PropertyUtils::buildClassPropertyDeclarations($config, $type);
        $out .= "\n";
        $out .= Constructor::buildHeader($config, $type);
        $out .= Constructor::buildPrimitiveContainerBody($config, $type);
        $out .= "    }\n\n";
        $out .= XMLUnserializeUtils::buildHeader($config, $type);
        $out .= XMLUnserializeUtils::buildDefaultBody($config, $type);
        $out .= "    }\n\n";
        $valueProperty = $type->getProperties()->getProperty('value');
        $out .= MethodUtils::createPrimitiveContainerSetter($config, $type, $valueProperty);
        $out .= "\n";
        $out .= MethodUtils::createDefaultGetter($config, $type, $valueProperty);
        $out .= "\n";
        $out .= MethodUtils::buildToString($config, $type);
        $out .= "\n";
        $out .= JSONSerializeUtils::buildHeader($config, $type);
        $out .= JSONSerializeUtils::buildBody($config, $type);
        $out .= "    }\n\n";
        $out .= XMLSerializeUtils::buildHeader($config, $type);
        $out .= XMLSerializeUtils::buildBody($config, $type);
        return $out . "    }\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildResourceContainerOrInlineResourceTypeClass(VersionConfig $config,
                                                                              Types $types,
                                                                              Type $type)
    {
        $out = PropertyUtils::buildClassPropertyDeclarations($config, $type);
        $out .= "\n";
        $out .= Constructor::buildHeader($config, $type);
        $out .= Constructor::buildResourceContainerBody($config, $type);
        $out .= "    }\n\n";
        $out .= XMLUnserializeUtils::buildHeader($config, $type);
        $out .= XMLUnserializeUtils::buildResourceContainerBody($config, $type);
        $out .= "    }\n\n";
        $out .= PropertyUtils::buildClassPropertyMethods($config, $types, $type);
        $out .= "\n";
        $out .= MethodUtils::buildToString($config, $type);
        $out .= "\n";
        $out .= JSONSerializeUtils::buildHeader($config, $type);
        $out .= JSONSerializeUtils::buildBody($config, $type);
        $out .= "    }\n\n";
        $out .= XMLSerializeUtils::buildHeader($config, $type);
        $out .= XMLSerializeUtils::buildBody($config, $type);
        return $out . "    }\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    protected static function buildDefaultTypeClass(VersionConfig $config, Types $types, Type $type)
    {
        $out = '';
        if (0 < count($type->getProperties())) {
            $out .= PropertyUtils::buildClassPropertyDeclarations($config, $type);
            $out .= "\n";
            $out .= Constructor::buildHeader($config, $type);
            $out .= "\n";
            $out .= Constructor::buildDefaultBody($config, $type);
            $out .= "    }\n\n";
            $out .= XMLUnserializeUtils::buildHeader($config, $type);
            $out .= XMLUnserializeUtils::buildDefaultBody($config, $type);
            $out .= "    }\n\n";
            $out .= PropertyUtils::buildClassPropertyMethods($config, $types, $type);
            $out .= "\n";
            $out .= MethodUtils::buildToString($config, $type);
        }
        $out .= "\n";
        $out .= JSONSerializeUtils::buildHeader($config, $type);
        $out .= JSONSerializeUtils::buildBody($config, $type);
        $out .= "    }\n\n";
        $out .= XMLSerializeUtils::buildHeader($config, $type);
        $out .= XMLSerializeUtils::buildBody($config, $type);
        return $out . "    }\n";
    }

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function generateTypeClass(VersionConfig $config, Types $types, Type $type)
    {
        switch ($typeKind = $type->getKind()) {
            case TypeKindEnum::PRIMITIVE:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/primitive_class.php';
            case TypeKindEnum::PRIMITIVE_CONTAINER:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/primitive_container_class.php';
            case TypeKindEnum::_LIST:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/list_class.php';

            case TypeKindEnum::RESOURCE_CONTAINER:
            case TypeKindEnum::RESOURCE_INLINE:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/resource_container_class.php';

            case TypeKindEnum::ELEMENT:
            case TypeKindEnum::RESOURCE:
            case TypeKindEnum::DOMAIN_RESOURCE;
            case TypeKindEnum::RESOURCE_COMPONENT:
                return require PHPFHIR_TEMPLATE_TYPES_DIR . '/default_class.php';

            default:

        }

//        if ($typeKind->isPrimitive()) {
//            $out .= static::buildPrimitiveTypeClass($config, $types, $type);
//        } elseif ($type->isPrimitiveContainer()) {
//            $out .= static::buildPrimitiveContainerTypeClass($config, $types, $type);
//        } elseif ($typeKind->isResourceContainer() || $typeKind->isInlineResource()) {
//            $out .= static::buildResourceContainerOrInlineResourceTypeClass($config, $types, $type);
//        } elseif (!$type->hasPrimitiveParent() && !$type->hasPrimitiveContainerParent()) {
//            $out .= static::buildDefaultTypeClass($config, $types, $type);
//        }

//        return $out . '}';
    }
}