<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKind;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */

$config = $version->getConfig();
$namespace = $version->getFullyQualifiedName(false);

$containerType = $types->getContainerType($version->getName());
if (null === $containerType) {
    throw new \RuntimeException(sprintf(
        'Unable to locate either "%s" or "%s" type',
        TypeKind::RESOURCE_CONTAINER->value,
        TypeKind::RESOURCE_INLINE->value
    ));
}

/** @var \DCarbone\PHPFHIR\Version\Definition\Type[] $innerTypes */
$innerTypes = [];
foreach ($containerType->getLocalProperties()->getAllPropertiesIterator() as $property) {
    if ($ptype = $property->getValueFHIRType()) {
        $innerTypes[$ptype->getFHIRName()] = $ptype;
    }
}

ksort($innerTypes, SORT_NATURAL);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>

use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_CONFIG); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_CONSTANTS); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_TYPE); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_TYPE_MAP); ?>;

/**
 * Class <?php echo PHPFHIR_CLASSNAME_VERSION_TYPEMAP; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class <?php echo PHPFHIR_CLASSNAME_VERSION_TYPEMAP; ?> implements <?php echo PHPFHIR_INTERFACE_TYPE_MAP; ?>

{
    private const TYPE_MAP = [
<?php foreach ($types->getNameSortedIterator() as $type) : ?>
        <?php echo $type->getTypeNameConst(true); ?> => <?php echo $type->getClassNameConst(true); ?>,
<?php endforeach; ?>    ];

    private const CONTAINABLE_TYPES = [
<?php foreach($innerTypes as $innerType) : ?>
        <?php echo $innerType->getTypeNameConst(true); ?> => <?php echo $innerType->getClassNameConst(true); ?>,
<?php endforeach; ?>    ];

    /**
     * Get fully qualified class name for FHIR Type name.  Returns null if type not found
     * @param string $typeName
     * @return string|null
     */
    public function getTypeClass(string $typeName): null|string
    {
        return self::TYPE_MAP[$typeName] ?? null;
    }

    /**
     * Returns the full internal class map
     * @return array
     */
    public function getMap(): array
    {
        return self::TYPE_MAP;
    }

    /**
     * Returns the full list of containable resource types
     * @return array
     */
    public function getContainableTypes(): array
    {
        return self::CONTAINABLE_TYPES;
    }

    /**
     * @param string $typeName Name of FHIR object reference by <?php echo $containerType->getFHIRName(); ?>

     * @return string|null Name of class as string or null if type is not contained in map
     */
    public function getContainedTypeClassName(string $typeName): null|string
    {
        return self::CONTAINABLE_TYPES[$typeName] ?? null;
    }

    /**
     * Will attempt to determine if the provided value is or describes a containable resource type
     * @param string|array|\SimpleXMLElement|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $type
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isContainableResource(string|array|\SimpleXMLElement|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $type): bool
    {
        $tt = gettype($type);
        if ('object' === $tt) {
            if ($type instanceof <?php echo PHPFHIR_INTERFACE_TYPE; ?>) {
                return in_array('\\' . $type::class, self::CONTAINABLE_TYPES, true);
            }
            return isset(self::CONTAINABLE_TYPES[$type->getName()]);
        }
        if ('string' === $tt) {
            return isset(self::CONTAINABLE_TYPES[$type]) || in_array('\\' . ltrim($type, '\\'), self::CONTAINABLE_TYPES, true);
        }
        if (isset($type[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE])) {
            return isset(self::CONTAINABLE_TYPES[$type[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]]);
        }
        return false;
    }

    /**
     * @param \SimpleXMLElement $node Parent element containing inline resource
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config
     * @return null|<?php echo $version->getfullyQualifiedName(true, PHPFHIR_INTERFACE_CONTAINED_TYPE); ?>

     */
    public function getContainedTypeFromXML(\SimpleXMLElement $node, <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config): null|<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>

    {
        $typeName = $node->getName();
        $className = self::getContainedTypeClassName($typeName);
        if (null === $className) {
            throw self::createdInvalidContainedTypeException($typeName);
        }
        /** @var \<?php echo ('' !== $namespace ? "{$namespace}\\" : '') . PHPFHIR_INTERFACE_CONTAINED_TYPE ?> $className */
        return $className::xmlUnserialize($node, null, $config);
    }

    /**
     * @param array|null $data
     * @return null|<?php echo $version->getfullyQualifiedName(true, PHPFHIR_INTERFACE_CONTAINED_TYPE); ?>

     */
    public function getContainedTypeFromArray(null|array $data): null|<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>

    {
        if (null === $data || [] === $data) {
            return null;
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf(
                '$data must be either an array or null, %s seen.',
                gettype($data)
            ));
        }
        $resourceType = null;
        if (isset($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE])) {
            $resourceType = $data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE];
        }
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
    private static function createdInvalidContainedTypeException(string $typeName): \UnexpectedValueException
    {
        return new \UnexpectedValueException(sprintf(
            'Type "%s" is not among the list of types allowed within a <?php echo $containerType->getFHIRName(); ?>',
            $typeName
        ));
    }
}
<?php return ob_get_clean();