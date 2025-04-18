<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();
$versionCoreFiles = $version->getVersionCoreFiles();
$imports = $type->getImports();

$resourceContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_CONTAINER_TYPE);
$containedTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_CONTAINED_TYPE);
$typeValidationTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_TRAIT_TYPE_VALIDATIONS);
$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);

$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);
$versionConstants = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS);
$versionContainedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);

$imports->addCoreFileImports(
    $resourceContainerInterface,
    $containedTypeInterface,
    $typeValidationTrait,
    $fhirVersion,

    $versionClass,
    $versionConstants,
    $versionContainedTypeInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

    namespace <?php echo $type->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class <?php echo $type->getClassName(); ?> implements <?php echo $resourceContainerInterface; ?>

{
    use <?php echo $typeValidationTrait; ?>;

    public const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;

    private const _FHIR_VALIDATION_RULES = [];

    /** @var null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?> */
    private null|<?php echo $versionContainedTypeInterface; ?> $containedType = null;

    public function __construct(null|<?php echo $versionContainedTypeInterface; ?> $containedType = null)
    {
        if (null !== $containedType) {
            $this->setContainedType($containedType);
        }
    }

    public function _getFHIRTypeName(): string
    {
        return self::FHIR_TYPE_NAME;
    }

    /* <?php echo basename(__FILE__) . ':' . __LINE__; ?> */
    public function _getFHIRVersion(): <?php echo $fhirVersion; ?>

    {
        return <?php echo $versionClass; ?>::getFHIRVersion();
    }

    /**
     * @return null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?>

     */
    public function getContainedType(): null|<?php echo $containedTypeInterface; ?>

    {
        return $this->contained ?? null;
    }

    /**
     * @param null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?> $containedType
     * @return static
     */
    public function setContainedType(null|<?php echo $containedTypeInterface; ?> $containedType): self
    {
        if (null === $containedType) {
            unset($this->containedType);
            return $this;
        }
        if (!($containedType instanceof <?php echo $versionContainedTypeInterface; ?>)) {
            throw new \InvalidArgumentException(sprintf(
                'Contained type must implement "%s", provided type "%s" does not.',
                <?php echo $versionContainedTypeInterface; ?>::class,
                $containedType::class,
            ));
        }
        $this->containedType = $containedType;
        return $this;
    }

    public function __toString(): string
    {
        return (string)($this->containedType ?? self::FHIR_TYPE_NAME);
    }

    /**
     * @return null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?>

     */
    public function jsonSerialize(): null|<?php echo $versionContainedTypeInterface; ?>

    {
        return $this->containedType ?? null;
    }
}
<?php
return ob_get_clean();
