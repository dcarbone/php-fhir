<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$imports = $coreFile->getImports();

$imports->addCoreFileImportsByName(
    PHPFHIR_CLASSNAME_CONSTANTS,
    PHPFHIR_INTERFACE_TYPE,
    PHPFHIR_INTERFACE_VERSION_TYPE_MAP,
);

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_TYPE);

$types = $version->getDefinition()->getTypes();

$containerType = $types->getContainerType($version);
if (null === $containerType) {
    throw new \RuntimeException(sprintf(
        'Unable to locate either "%s" or "%s" type',
        TypeKindEnum::RESOURCE_CONTAINER->value,
        TypeKindEnum::RESOURCE_INLINE->value
    ));
}

/** @var \DCarbone\PHPFHIR\Version\Definition\Type[] $innerTypes */
$innerTypes = [];
foreach ($containerType->getProperties()->getAllPropertiesIterator() as $property) {
    if ($ptype = $property->getValueFHIRType()) {
        $innerTypes[$ptype->getFHIRName()] = $ptype;
    }
}

ksort($innerTypes, SORT_NATURAL);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo PHPFHIR_CLASSNAME_VERSION_TYPE_MAP; ?> implements <?php echo PHPFHIR_INTERFACE_VERSION_TYPE_MAP; ?>

{
    private const _TYPE_MAP = [
<?php foreach ($types->getNameSortedIterator() as $type) : ?>
        <?php echo $type->getTypeNameConst(true); ?> => <?php echo $type->getClassNameConst(true); ?>,
<?php endforeach; ?>    ];

    private const _CONTAINABLE_TYPES = [
<?php foreach($innerTypes as $innerType) : ?>
        <?php echo $innerType->getTypeNameConst(true); ?> => <?php echo $innerType->getClassNameConst(true); ?>,
<?php endforeach; ?>    ];

    /**
     * Get fully qualified class name for FHIR Type name.  Returns null if type not found
     * @param string $typeName
     * @return string|null
     */
    public static function getTypeClassName(string $typeName): null|string
    {
        return self::_TYPE_MAP[$typeName] ?? null;
    }

    /**
     * Returns the full internal class map
     * @return array
     */
    public static function getMap(): array
    {
        return self::_TYPE_MAP;
    }

    /**
     * Returns the full list of containable resource types
     * @return array
     */
    public static function getContainableTypes(): array
    {
        return self::_CONTAINABLE_TYPES;
    }

    /**
     * @param string $typeName Name of FHIR object reference by <?php echo $containerType->getFHIRName(); ?>

     * @return string|null Name of class as string or null if type is not contained in map
     */
    public static function getContainedTypeClassName(string $typeName): null|string
    {
        return self::_CONTAINABLE_TYPES[$typeName] ?? null;
    }

    /**
     * Will attempt to determine if the provided value is or describes a containable resource type
     * @param string|array|\SimpleXMLElement|<?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isContainableResource(string|array|\SimpleXMLElement|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $type): bool
    {
        $tt = gettype($type);
        if ('object' === $tt) {
            if ($type instanceof <?php echo PHPFHIR_INTERFACE_TYPE; ?>) {
                return ($type instanceof <?php echo PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE; ?>);
            }
            return isset(self::_CONTAINABLE_TYPES[$type->getName()]);
        }
        if ('string' === $tt) {
            return isset(self::_CONTAINABLE_TYPES[$type]) || in_array('\\' . ltrim($type, '\\'), self::_CONTAINABLE_TYPES, true);
        }
        if (isset($type[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE])) {
            return isset(self::_CONTAINABLE_TYPES[$type[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]]);
        }
        return false;
    }

    /**
     * @param \SimpleXMLElement $node Parent element containing inline resource
     * @return string Fully qualified class name of contained resource type
     */
    public static function getContainedTypeClassNameFromXML(\SimpleXMLElement $node): string
    {
        $typeName = $node->getName();
        $className = self::getContainedTypeClassName($typeName);
        if (null === $className) {
            throw self::createdInvalidContainedTypeException($typeName);
        }
        return $className;
    }

    /**
     * @param array $data
     * @return string Fully qualified class name of contained resource type
     */
    public static function getContainedTypeClassNameFromArray(array $data): string
    {
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
        $className = self::getContainedTypeClassName($resourceType);
        if (null === $className) {
            throw self::createdInvalidContainedTypeException($resourceType);
        }
        return $className;
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