<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Types;

/**
 * Class PropertyUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class PropertyUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildClassPropertyDeclarations(Config $config, Type $type)
    {
        $out = '';
        foreach ($type->getProperties()->getSortedIterator() as $property) {
            $out .= "\n    /**\n";
            $out .= $property->getDocBlockDocumentationFragment();
            $out .= "     * @var {$property->getPHPTypeName()}";
            if ($property->isCollection()) {
                $out .= '[]';
            }
            $out .= "\n     */\n";
            $out .= '    public ';
            $out .= NameUtils::getPropertyVariableName($property->getName());
            if ($property->isCollection()) {
                $out .= ' = []';
            } else {
                $out .= ' = null';
            }
            $out .= ";\n";
        }
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Types $types
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildClassPropertyMethods(Config $config, Types $types, Type $type)
    {
        $sortedProperties = $type->getProperties()->getSortedIterator();

        $out = '';
        foreach ($sortedProperties as $property) {
            $propType = $property->getValueType();
            if (null === $propType) {
                $config->getLogger()->warning(sprintf(
                    'Unable to locate FHIR Type for Type %s Property %s',
                    $type,
                    $property
                ));
                continue;
            }
            if ('' !== $out) {
                $out .= "\n";
            }
            if ($propType->isPrimitive() || $propType->hasPrimitiveParent() || $propType->isPrimitiveContainer() || $propType->hasPrimitiveContainerParent()) {
                $out .= MethodUtils::createPrimitiveSetter($config, $type, $property);
            } else {
                $out .= MethodUtils::createDefaultSetter($config, $type, $property);
            }
            $out .= "\n";
            $out .= MethodUtils::createGetter($config, $property);
            $out .= "\n";
        }

        return $out;
    }
}