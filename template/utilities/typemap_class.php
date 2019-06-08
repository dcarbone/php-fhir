<?php

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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getNamespace(false);

$containerType = $types->getContainerType();
if (null === $containerType) {
    throw new \RuntimeException(sprintf(
        'Unable to locate either "%s" or "%s" type',
        TypeKindEnum::RESOURCE_CONTAINER,
        TypeKindEnum::RESOURCE_INLINE
    ));
}

/** @var \DCarbone\PHPFHIR\Definition\Type[] $innerTypes */
$innerTypes = [];
foreach ($containerType->getProperties()->getIterator() as $property) {
    if ($ptype = $property->getValueFHIRType()) {
        $innerTypes[$ptype->getFHIRName()] = $ptype;
    }
}

ksort($innerTypes, SORT_NATURAL);

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Class PHPFHIRTypeMap<?php if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class PHPFHIRTypeMap
{
    /**
     * This array represents every type known to this lib
     * @var array
     */
    private static $_typeMap = [
<?php foreach ($types->getSortedIterator() as $type) : ?>
        <?php echo $type->getTypeNameConst(); ?> => <?php echo $type->getClassNameConst(); ?>,
<?php endforeach; ?>    ];

    /**
     * This is the list of resource types that are allowed to be contained within a <?php echo $containerType->getFHIRName(); ?> type
     * @var array
     */
    private static $_resourceMap = [
<?php foreach($innerTypes as $innerType) : ?>
        <?php echo $innerType->getTypeNameConst(); ?> => <?php echo $innerType->getClassNameConst(); ?>,
<?php endforeach; ?>    ];

    /**
     * Get fully qualified class name for FHIR Type name.  Returns null if type not found
     * @param string $typeName
     * @return string|null
     */
    public static function getTypeClass($typeName) {
        return (is_string($typeName) && isset(self::$_typeMap[$typeName])) ? self::$_typeMap[$typeName] : null;
    }

    /**
     * Returns the full internal class map
     * @return array
     */
    public static function getMap() {
        return self::$_typeMap;
    }

    /**
     * @param string $typeName Name of FHIR object reference by <?php echo $containerType->getFHIRName(); ?>

     * @return string|null Name of class as string or null if type is not contained in map
     */
    public static function getContainedTypeClassName($typeName)
    {
        return (is_string($typeName) && isset(self::$_resourceMap[$typeName])) ? self::$_resourceMap[$typeName] : null;
    }

    /**
     * Will attempt to determine if the provided value is or describes a containable resource type
     * @param object|string|array $type
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isContainableResource($type) {
        $tt = gettype($type);
        if ('object' === $tt) {
            if ($type instanceof PHPFHIRTypeInterface) {
                return in_array('\\'.get_class($type), self::$_resourceMap, true);
            }
            if ($type instanceof \SimpleXMLElement) {
                return isset(self::$_resourceMap[$type->getName()]);
            }
            throw new \InvalidArgumentException(sprintf(
                'Expected "$type" to be instance of "%s" or "%s", saw "%s"',
                '\\SimpleXMLElement',
                get_class('PHPFHIRTypeInterface'),
                get_class($type)
            ));
        }
        if ('string' === $tt) {
            return isset(self::$_resourceMap[$type]) || in_array('\\'.ltrim($type, '\\'), self::$_resourceMap, true);
        }
        if ('array' === $tt) {
            if (isset($type[FHIR_JSON_FIELD_RESOURCE_TYPE])) {
                return isset(self::$_resourceMap[$type[FHIR_JSON_FIELD_RESOURCE_TYPE]]);
            }
            return false;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unable to process input of type "%s"',
            gettype($type)
        ));
    }

    /**
     * @param \SimpleXMLElement $sxe Parent element containing inline resource
     * @return object|null
     */
    public static function getContainedTypeFromXML(\SimpleXMLElement $sxe)
    {
        foreach($sxe->children() as $child) {
            $typeName = $child->getName();
            $className = self::getResourceContainerTypeClass($typeName);
            if (null === $className) {
                throw self::createdInvalidContainedTypeException($typeName);
            }
            return $className::xmlUnserialize($child);
        }
        return null;
    }

    /**
     * @param array|null $data
     * @return object|null
     */
    public static function getContainedTypeFromArray($data)
    {
        if (null === $data) {
            return null;
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf(
                '$data must be either an array or null, %s seen.',
                gettype($data)
            ));
        }
        if ([] === $data) {
            return null;
        }
        $resourceType = isset($data[FHIR_JSON_FIELD_RESOURCE_TYPE]) ? $data[FHIR_JSON_FIELD_RESOURCE_TYPE] : null;
        if (null === $resourceType) {
            throw new \DomainException(sprintf(
                'Unable to determine contained Resource type from input (missing "%s" key).  Keys: ["%s"]',
                FHIR_JSON_FIELD_RESOURCE_TYPE,
                implode('","', array_keys($data))
            ));
        }
        unset($data[FHIR_JSON_FIELD_RESOURCE_TYPE]);
        $className = self::getResourceContainerTypeClass($resourceType);
        if (null === $className) {
            throw self::createdInvalidContainedTypeException($resourceType);
        }
        return new $className($data);
    }

    /**
     * @param string $typeName
     * @return \UnexpectedValueException
     */
    private static function createdInvalidContainedTypeException($typeName) {
        return new \UnexpectedValueException(sprintf(
            'Type "%s" is not among the list of types allowed within a <?php echo $containerType->getFHIRName(); ?>',
            $typeName
        ));
    }
}
<?php return ob_get_clean();