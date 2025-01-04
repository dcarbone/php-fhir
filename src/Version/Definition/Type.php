<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TestTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Enumeration\EnumerationValue;
use DomainException;
use LogicException;
use SimpleXMLElement;

/**
 * Class Type
 * @package DCarbone\PHPFHIR\Definition
 */
class Type
{
    use DocumentationTrait;
    use SourceTrait;

    /** @var \DCarbone\PHPFHIR\Config */
    private Config $config;

    /** @var \DCarbone\PHPFHIR\Version */
    private Version $version;

    /**
     * The raw name of the FHIR element this type was created from
     * @var string
     */
    private string $fhirName;

    /** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum|null */
    private null|TypeKindEnum $kind = null;

    /** @var string */
    private string $className;

    /** @var \DCarbone\PHPFHIR\Version\Definition\Properties */
    private Properties $localProperties;

    /** @var null|string */
    private null|string $parentTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Version\Definition\Type */
    private null|Type $parentType = null;

    /** @var int */
    private int $minLength = 0;
    /** @var int */
    private int $maxLength = PHPFHIR_UNLIMITED;
    /** @var null|string */
    private null|string $pattern = null;

    /** @var null|\DCarbone\PHPFHIR\Version\Definition\Type */
    private null|Type $componentOfType = null;

    /** @var \DCarbone\PHPFHIR\Version\Definition\Enumeration */
    private Enumeration $enumeration;

    /** @var array */
    private array $unionOf = [];

    /** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum */
    private PrimitiveTypeEnum $primitiveType;

    /** @var null|string */
    private null|string $restrictionBaseFHIRName = null;

    /** @var null|\DCarbone\PHPFHIR\Version\Definition\Type */
    private null|Type $restrictionBaseFHIRType = null;

    /** @var bool */ // TODO: what the hell is this...?
    private bool $mixed = false;

    /** @var bool */
    private bool $containedType = false;
    /** @var bool */
    private bool $valueContainer = false;
    /** @var bool */
    private bool $commentContainer = false;

    /** @var \DCarbone\PHPFHIR\Version\Definition\TypeImports */
    private TypeImports $imports;

    /**
     * Type constructor.
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Version $version
     * @param string $fhirName
     * @param \SimpleXMLElement|null $sourceSXE
     * @param string $sourceFilename
     */
    public function __construct(
        Config                $config,
        Version               $version,
        string                $fhirName,
        null|SimpleXMLElement $sourceSXE = null,
        string                $sourceFilename = ''
    )
    {
        if ('' === ($fhirName = trim($fhirName))) {
            throw new DomainException('$fhirName must be defined');
        }
        $this->config = $config;
        $this->version = $version;
        $this->fhirName = $fhirName;
        $this->sourceSXE = $sourceSXE;
        $this->sourceFilename = $sourceFilename;
        $this->localProperties = new Properties($config, $version, $this);
        $this->enumeration = new Enumeration();
        $this->imports = new TypeImports($this);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $vars = get_object_vars($this);
        unset($vars['config']);
        return $vars;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version
     */
    public function getVersion(): Version
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getFHIRName(): string
    {
        return $this->fhirName;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\TypeImports
     */
    public function getImports(): TypeImports
    {
        return $this->imports;
    }

    /**
     * @param bool $withConstClass
     * @param string $prefix
     * @return string
     */
    public function getConstName(bool $withConstClass, string $prefix = ''): string
    {
        if ($withConstClass) {
            $cn = sprintf('%s::', PHPFHIR_CLASSNAME_VERSION_CONSTANTS);
        } else {
            $cn = '';
        }

        return sprintf('%s%s%s', $cn, strtoupper($prefix), NameUtils::getConstName($this->getFHIRName()));
    }

    /**
     * @param bool $withClass
     * @return string
     */
    public function getTypeNameConst(bool $withClass): string
    {
        return $this->getConstName($withClass, 'TYPE_NAME_');
    }

    /**
     * @param bool $withClass
     * @return string
     */
    public function getClassNameConst(bool $withClass): string
    {
        return $this->getConstName($withClass, 'TYPE_CLASS_');
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\TypeKindEnum|null
     */
    public function getKind(): null|TypeKindEnum
    {
        return $this->kind;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum $kind
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setKind(TypeKindEnum $kind): Type
    {
        if (isset($this->kind) && $this->kind !== $kind) {
            throw new LogicException(
                sprintf(
                    'Cannot overwrite Type % s Kind from %s to %s',
                    $this->getFHIRName(),
                    $this->kind->value,
                    $kind->value
                )
            );
        }
        $this->kind = $kind;
        return $this;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setPrimitiveType(PrimitiveTypeEnum $primitiveType): Type
    {
        if (isset($this->primitiveType) && $this->primitiveType === $primitiveType) {
            throw new LogicException(
                sprintf(
                    'Cannot overwrite Type "%s" PrimitiveType from "%s" to "%s"',
                    $this->getFHIRName(),
                    $this->primitiveType->value,
                    $primitiveType->value
                )
            );
        }
        $this->primitiveType = $primitiveType;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeNamespace(): string
    {
        $bits = [];
        foreach ($this->getParentTypes() as $parent) {
            array_unshift($bits, $parent->getClassName());
        }
        if ($ctype = $this->getComponentOfType()) {
            $bits[] = $ctype->getClassName();
        }
        $ns = PHPFHIR_NAMESPACE_VERSION_TYPES;
        if ([] !== $bits) {
            $ns .= PHPFHIR_NAMESPACE_SEPARATOR . implode(PHPFHIR_NAMESPACE_SEPARATOR, $bits);
        }
        return $ns;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        if (!isset($this->className)) {
            $this->className = NameUtils::getTypeClassName($this->getFHIRName());
        }
        return $this->className;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace(bool $leadingSlash): string
    {
        return $this->getVersion()->getFullyQualifiedName($leadingSlash, $this->getTypeNamespace());
    }

    public function get()
    {

    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestTypeEnum $testType
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedTestNamespace(TestTypeEnum $testType, bool $leadingSlash): string
    {
        return $this->getVersion()->getFullyQualifiedTestsName($testType, $leadingSlash, $this->getTypeNamespace());
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassName(bool $leadingSlash): string
    {
        return $this->getVersion()->getFullyQualifiedName($leadingSlash, $this->getTypeNamespace(), $this->getClassName());
    }

    /**
     * @return string
     */
    public function getTestClassName(): string
    {
        return sprintf('%sTest', $this->getClassName());
    }

    /**
     * @param $testType
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedTestClassName($testType, bool $leadingSlash): string
    {
        return $this->getVersion()->getFullyQualifiedTestsName($testType, $leadingSlash, $this->getTypeNamespace(), $this->getTestClassName());
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Properties
     */
    public function getLocalProperties(): Properties
    {
        return $this->localProperties;
    }

    /**
     * Returns true if this type has any locally defined properties.
     *
     * @return bool
     */
    public function hasLocalProperties(): bool
    {
        return count($this->localProperties) > 0;
    }

    /**
     * @return bool
     */
    public function hasLocalPropertiesWithValidations(): bool
    {
        foreach ($this->getlocalProperties()->getAllSortedPropertiesIterator() as $property) {
            if ([] !== $property->buildValidationMap()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getAllPropertiesIterator(): iterable
    {
        $properties = [];
        foreach ($this->getLocalProperties()->getLocalPropertiesIterator() as $property) {
            $properties[$property->getName()] = $property;
        }
        foreach ($this->getParentTypes() as $parentType) {
            foreach ($parentType->getAllPropertiesIterator() as $property) {
                if (!isset($properties[$property->getName()])) {
                    $properties[$property->getName()] = $property;
                }
            }
        }
        return \SplFixedArray::fromArray($properties, preserveKeys: false);
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type[]
     */
    public function getParentTypes(): array
    {
        $parents = [];
        $p = $this->getParentType();
        while (null !== $p) {
            $parents[] = $p;
            $p = $p->getParentType();
        }
        return $parents;
    }

    /**
     * @return bool
     */
    public function isRootType(): bool
    {
        return null === $this->getParentType();
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function getRootType(): Type
    {
        if ($this->isRootType()) {
            return $this;
        }
        $parents = $this->getParentTypes();
        return end($parents);
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getParentType(): null|Type
    {
        return $this->parentType;
    }

    /**
     * Returns true if there is any parent of this type that has local properties.
     *
     * @return bool
     */
    public function hasParentWithLocalProperties(): bool
    {
        $parent = $this->getParentType();
        $localsFound = false;

        while (null !== $parent && false === $localsFound) {
            $localsFound = $parent->hasLocalProperties();
            $parent = $parent->getParentType();
        }

        return $localsFound;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setParentType(Type $type): Type
    {
        $this->parentType = $type;
        $this->setParentTypeName($type->getFHIRName());
        return $this;
    }

    /**
     * @return null|string
     */
    public function getParentTypeName(): null|string
    {
        return $this->parentTypeName;
    }

    /**
     * @param string|null $parentTypeName
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setParentTypeName(null|string $parentTypeName): Type
    {
        $this->parentTypeName = $parentTypeName;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasParent(): bool
    {
        return null !== $this->getParentTypeName() || null !== $this->getParentType();
    }

    /**
     * @return bool
     */
    public function hasPrimitiveParent(): bool
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->getKind() === TypeKindEnum::PRIMITIVE) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasPrimitiveContainerParent(): bool
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->getKind() === TypeKindEnum::PRIMITIVE_CONTAINER) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isResourceType(): bool
    {
        return str_contains($this->getFullyQualifiedNamespace(false), '\\FHIRResource\\');
    }

    /**
     * @return int
     */
    public function getMinLength(): int
    {
        return $this->minLength;
    }

    /**
     * @param int $minLength
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setMinLength(int $minLength): Type
    {
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setMaxLength(int $maxLength): Type
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPattern(): null|string
    {
        return $this->pattern;
    }

    /**
     * @param string|null $pattern
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setPattern(null|string $pattern): Type
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getComponentOfType(): null|Type
    {
        return $this->componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setComponentOfType(Type $type): Type
    {
        $this->componentOfType = $type;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Enumeration
     */
    public function getEnumeration(): Enumeration
    {
        return $this->enumeration;
    }

    /**
     * @param mixed $enumValue
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function addEnumerationValue(EnumerationValue $enumValue): Type
    {
        $this->enumeration->addValue($enumValue);
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnumerated(): bool
    {
        return 0 !== count($this->getEnumeration());
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum
     */
    public function getPrimitiveType(): PrimitiveTypeEnum
    {
        return $this->primitiveType;
    }

    /**
     * @return bool
     */
    public function hasPrimitiveType(): bool
    {
        return isset($this->primitiveType);
    }

    /**
     * @return array
     */
    public function getUnionOf(): array
    {
        return $this->unionOf;
    }

    /**
     * @param array $unionOf
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setUnionOf(array $unionOf): Type
    {
        $this->unionOf = $unionOf;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRestrictionBaseFHIRName(): null|string
    {
        return $this->restrictionBaseFHIRName;
    }

    /**
     * @param string $restrictionBaseFHIRName
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setRestrictionBaseFHIRName(string $restrictionBaseFHIRName): Type
    {
        $this->restrictionBaseFHIRName = $restrictionBaseFHIRName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getRestrictionBaseFHIRType(): null|Type
    {
        return $this->restrictionBaseFHIRType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setRestrictionBaseFHIRType(Type $type): Type
    {
        $this->restrictionBaseFHIRType = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMixed(): bool
    {
        return $this->mixed;
    }

    /**
     * @param bool $mixed
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setMixed(bool $mixed): Type
    {
        $this->mixed = $mixed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContainedType(): bool
    {
        return $this->containedType;
    }

    /**
     * @param bool $containedType
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setContainedType(bool $containedType): Type
    {
        $this->containedType = $containedType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValueContainer(): bool
    {
        return $this->valueContainer;
    }

    /**
     * @param bool $valueContainer
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setValueContainer(bool $valueContainer): Type
    {
        $this->valueContainer = $valueContainer;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasValueContainerParent(): bool
    {
        foreach ($this->getParentTypes() as $parent) {
            if ($parent->isValueContainer()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns map of [$interface_name => $interface_namespace]
     *
     * $interface_namespace does NOT contain leading slash!
     *
     * @return array
     */
    public function getDirectlyImplementedInterfaces(): array
    {
        $interfaces = [];
        $parentType = $this->getParentType();
        $coreFiles = $this->version->getConfig()->getCoreFiles();
        $versionCoreFiles = $this->version->getCoreFiles();

        if (null === $parentType) {
            if ($this->isCommentContainer()) {
                $interfaces[PHPFHIR_INTERFACE_COMMENT_CONTAINER] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_INTERFACE_COMMENT_CONTAINER)
                    ->getFullyQualifiedNamespace(false);
            }
            if ($this->isContainedType()) {
                $interfaces[PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE] = $versionCoreFiles
                    ->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE)
                    ->getFullyQualifiedNamespace(false);
            } else if ($this->getKind() === TypeKindEnum::PRIMITIVE) {
                $interfaces[PHPFHIR_INTERFACE_PRIMITIVE_TYPE] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_INTERFACE_PRIMITIVE_TYPE)
                    ->getFullyQualifiedNamespace(false);
            } else {
                $interfaces[PHPFHIR_INTERFACE_TYPE] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_INTERFACE_TYPE)
                    ->getFullyQualifiedNamespace(false);
            }
        } elseif ($this->isContainedType() && !$parentType->isContainedType()) {
            $interfaces[PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE] = $versionCoreFiles
                ->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE)
                ->getFullyQualifiedNamespace(false);
        }

        return $interfaces;
    }

    /**
     * @return array
     */
    public function getDirectlyUsedTraits(): array
    {
        $traits = [];
        $parentType = $this->getParentType();
        $coreFiles = $this->version->getConfig()->getCoreFiles();

        if (null === $parentType) {
            // if this type has no parent(s), try to add all traits

            if ($this->isCommentContainer()) {
                $traits[PHPFHIR_TRAIT_COMMENT_CONTAINER] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_TRAIT_COMMENT_CONTAINER)
                    ->getFullyQualifiedNamespace(false);
            }

            // these must only be added if the type has local properties
            if ($this->hasLocalProperties()) {
                $traits[PHPFHIR_TRAIT_SOURCE_XMLNS] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_TRAIT_SOURCE_XMLNS)
                    ->getFullyQualifiedNamespace(false);
            }
        } else if (!$parentType->hasLocalProperties()) {
            // if this type _does_ have a parent, only add these traits if the parent does not have local properties
            $traits[PHPFHIR_TRAIT_SOURCE_XMLNS] = $coreFiles
                ->getCoreFileByEntityName(PHPFHIR_TRAIT_SOURCE_XMLNS)
                ->getFullyQualifiedNamespace(false);
        }

        return $traits;
    }

    /**
     * @return bool
     */
    public function hasCommentContainerParent(): bool
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->isCommentContainer()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param bool $commentContainer
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setCommentContainer(bool $commentContainer): Type
    {
        $this->commentContainer = $commentContainer;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCommentContainer(): bool
    {
        return $this->commentContainer;
    }

    /**
     * Returns true if this class should be defined as abstract
     *
     * TODO(@dcarbone): this is a quick hack, find better implementation...
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->getFHIRName() === 'Base';
    }

    /**
     * Returns true if this type has a concrete parent
     *
     * @return bool
     */
    public function hasConcreteParent(): bool
    {
        $p = $this->getParentType();
        while ($p !== null) {
            if (!$p->isAbstract()) {
                return true;
            }
            $p = $p->getParentType();
        }
        return false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFHIRName();
    }
}