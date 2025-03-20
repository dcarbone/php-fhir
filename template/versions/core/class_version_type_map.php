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
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();
$versionCoreFiles = $version->getVersionCoreFiles();
$imports = $coreFile->getImports();

$constantsClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CONSTANTS);
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$typeMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_TYPE_MAP);

$versionConstants = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS);
$versionContainedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);

$imports->addCoreFileImports(
    $constantsClass,
    $typeInterface,
    $typeMapInterface,

    $versionConstants,
    $versionContainedTypeInterface,
);

$types = $version->getDefinition()->getTypes();

$containerType = $types->getContainerType();
if (null === $containerType) {
    throw new \RuntimeException(sprintf(
        'Unable to locate either "%s" or "%s" type',
        TypeKindEnum::RESOURCE_CONTAINER->value,
        TypeKindEnum::RESOURCE_INLINE->value
    ));
}

/** @var \DCarbone\PHPFHIR\Version\Definition\Type[] $innerTypes */
$innerTypes = [];
foreach ($containerType->getProperties()->getIterator() as $property) {
    if ($ptype = $property->getValueFHIRType()) {
        $innerTypes[$ptype->getFHIRName()] = $ptype;
    }
}

ksort($innerTypes, SORT_NATURAL);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $typeMapInterface; ?>

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
     * Returns the full internal class map
     * @return array
     */
    public static function getMap(): array
    {
        return self::_TYPE_MAP;
    }

    /**
     * Returns a map of [ "typeName" => "typeClass" ] of all types that may be contained within a resource container.
     *
     * @return array
     */
    public static function getContainableTypes(): array
    {
        return self::_CONTAINABLE_TYPES;
    }

    /**
     * Returns the fully qualified classname for the provided input, if it represents a FHIR type.
     *
     * @param string|\stdClass|\SimpleXMLElement $input Expects either name of type or unserialized JSON or XML.
     * @return string|null
     */
    public static function getTypeClassname(string|\stdClass|\SimpleXMLElement $input): null|string
    {
        if (is_string($input)) {
            return self::_TYPE_MAP[$input] ?? null;
        } else if ($input instanceof \SimpleXMLElement) {
            return self::_TYPE_MAP[$input->getName()] ?? null;
        } else if (isset($input-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>)) {
            return self::_TYPE_MAP[$input-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>] ?? null;
        } else {
            return null;
        }
    }

    /**
     * Attempts to return the fully qualified classname of a contained type from the provided input, if it
     * represents one.
     *
     * @param string|\stdClass|\SimpleXMLElement $input Expects either name of type or unserialized JSON or XML.
     * @return string|null Name of class as string or null if type is not contained in map
     */
    public static function getContainedTypeClassname(string|\stdClass|\SimpleXMLElement $input): null|string
    {
        $classname = self::getTypeClassname($input);
        if (null !== $classname && in_array($classname, self::_CONTAINABLE_TYPES, true)) {
            return $classname;
        }
        return null;
    }

    /**
     * Attempts to determine if the provided value is or represents a containable resource type
     *
     * @param string|\stdClass|\SimpleXMLElement|<?php echo $typeInterface->getFullyQualifiedName(true); ?> $input
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isContainableType(string|\stdClass|\SimpleXMLElement|<?php echo $typeInterface; ?> $input): bool
    {
        if ($input instanceof <?php echo $typeInterface; ?>) {
            return ($input instanceof <?php echo $versionContainedTypeInterface; ?>);
        } else if (is_string($input) && str_contains($input, '\\')) {
            return isset(self::_CONTAINABLE_TYPES[$input]) || in_array('\\' . ltrim($input, '\\'), self::_CONTAINABLE_TYPES, true);
        } else {
            return null !== self::getContainedTypeClassname($input);
        }
    }

    /**
     * @param \SimpleXMLElement $node Parent element containing inline resource
     * @return string Fully qualified class name of contained resource type
     * @throws \UnexpectedValueException
     */
    public static function mustGetContainedTypeClassnameFromXML(\SimpleXMLElement $node): string
    {
        $typeName = $node->getName();
        if (isset(self::_CONTAINABLE_TYPES[$typeName])) {
            return self::_CONTAINABLE_TYPES[$typeName];
        }
        throw self::createdInvalidContainedTypeException($typeName);
    }

    /**
     * @param \stdClass $decoded
     * @return string Fully qualified class name of contained resource type
     * @throws \UnexpectedValueException
     * @throws \DomainException
     */
    public static function mustGetContainedTypeClassnameFromJSON(\stdClass $decoded): string
    {
        if (!isset($decoded-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>)) {
            throw new \DomainException(sprintf(
                'Unable to determine contained Resource type from input (missing "%s" key).  Keys: ["%s"]',
                <?php echo $constantsClass; ?>::JSON_FIELD_RESOURCE_TYPE,
                implode('","', array_keys((array)$decoded))
            ));
        }
        if (isset(self::_CONTAINABLE_TYPES[$decoded-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>])) {
            return self::_CONTAINABLE_TYPES[$decoded-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>];
        }
        throw self::createdInvalidContainedTypeException($decoded-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>);
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