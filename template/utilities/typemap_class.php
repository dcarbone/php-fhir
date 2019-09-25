<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Class <?php echo PHPFHIR_CLASSNAME_TYPEMAP; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class <?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>

{
    /**
     * This array represents every type known to this lib
     * @var array
     */
    private static $_typeMap = [
<?php foreach ($types->getSortedIterator() as $type) : ?>
        <?php echo $type->getTypeNameConst(true); ?> => <?php echo $type->getClassNameConst(true); ?>,
<?php endforeach; ?>    ];

    /**
     * This is the list of resource types that are allowed to be contained within a <?php echo $containerType->getFHIRName(); ?> type
     * @var array
     */
    private static $_containableTypes = [
<?php foreach($innerTypes as $innerType) : ?>
        <?php echo $innerType->getTypeNameConst(true); ?> => <?php echo $innerType->getClassNameConst(true); ?>,
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
     * Returns the full list of containable resource types
     * @return array
     */
    public static function getContainableTypes() {
        return self::$_containableTypes;
    }

    /**
     * @param string $typeName Name of FHIR object reference by <?php echo $containerType->getFHIRName(); ?>

     * @return string|null Name of class as string or null if type is not contained in map
     */
    public static function getContainedTypeClassName($typeName)
    {
        return (is_string($typeName) && isset(self::$_containableTypes[$typeName])) ? self::$_containableTypes[$typeName] : null;
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
            if ($type instanceof <?php echo PHPFHIR_INTERFACE_TYPE; ?>) {
                return in_array('\\'.get_class($type), self::$_containableTypes, true);
            }
            if ($type instanceof \SimpleXMLElement) {
                return isset(self::$_containableTypes[$type->getName()]);
            }
            throw new \InvalidArgumentException(sprintf(
                'Expected "$type" to be instance of "<?php echo $config->getNamespace(true) . '\\' . PHPFHIR_INTERFACE_TYPE; ?>" or "%s", saw "%s"',
                '\\SimpleXMLElement',
                get_class($type)
            ));
        }
        if ('string' === $tt) {
            return isset(self::$_containableTypes[$type]) || in_array('\\'.ltrim($type, '\\'), self::$_containableTypes, true);
        }
        if ('array' === $tt) {
            if (isset($type[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE])) {
                return isset(self::$_containableTypes[$type[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]]);
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
     * @return \<?php echo ('' !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_INTERFACE_CONTAINED_TYPE ?>|null
     */
    public static function getContainedTypeFromXML(\SimpleXMLElement $sxe)
    {
        $typeName = $sxe->getName();
        $className = self::getContainedTypeClassName($typeName);
        if (null === $className) {
            throw self::createdInvalidContainedTypeException($typeName);
        }
        /** @var \<?php echo ('' !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_INTERFACE_CONTAINED_TYPE ?> $className */
        return $className::xmlUnserialize($sxe);
    }

    /**
     * @param array|null $data
     * @return \<?php echo ('' !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_INTERFACE_CONTAINED_TYPE ?>|null
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
        $resourceType = isset($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]) ? $data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE] : null;
        if (null === $resourceType) {
            throw new \DomainException(sprintf(
                'Unable to determine contained Resource type from input (missing "%s" key).  Keys: ["%s"]',
                <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE,
                implode('","', array_keys($data))
            ));
        }
        unset($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
        $className = self::getContainedTypeClassName($resourceType);
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