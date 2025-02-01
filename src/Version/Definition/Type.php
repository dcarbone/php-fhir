<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Builder\Imports;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
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
    use DocumentationTrait,
        SourceTrait;

    /** @var \DCarbone\PHPFHIR\Version */
    private Version $_version;

    /**
     * The raw name of the FHIR element this type was created from
     * @var string
     */
    private string $_fhirName;

    /** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum|null */
    private null|TypeKindEnum $_kind = null;

    /** @var string */
    private string $_className;

    /** @var \DCarbone\PHPFHIR\Version\Definition\Properties */
    private Properties $_properties;

    /** @var null|string */
    private null|string $_parentTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Version\Definition\Type */
    private null|Type $_parentType = null;

    /** @var int */
    private int $_minLength = 0;
    /** @var int */
    private int $_maxLength = PHPFHIR_UNLIMITED;
    /** @var null|string */
    private null|string $_pattern = null;

    /** @var null|\DCarbone\PHPFHIR\Version\Definition\Type */
    private null|Type $_componentOfType = null;

    /** @var \DCarbone\PHPFHIR\Version\Definition\Enumeration */
    private Enumeration $_enumeration;

    /** @var array */
    private array $_unionOf = [];

    /** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum */
    private PrimitiveTypeEnum $_primitiveType;

    /** @var null|string */
    private null|string $_restrictionBaseFHIRName = null;

    /** @var null|\DCarbone\PHPFHIR\Version\Definition\Type */
    private null|Type $_restrictionBaseFHIRType = null;

    /** @var bool */ // TODO: what the hell is this...?
    private bool $_mixed = false;

    /** @var bool */
    private bool $_containedType = false;
    /** @var bool */
    private bool $_primitiveContainer = false;
    /** @var bool */
    private bool $_commentContainer = false;
    /** @var \DCarbone\PHPFHIR\Builder\Imports */
    private Imports $_imports;

    /**
     * Type constructor.
     * @param \DCarbone\PHPFHIR\Version $version
     * @param string $fhirName
     * @param \SimpleXMLElement|null $sourceSXE
     * @param string $sourceFilename
     */
    public function __construct(Version               $version,
                                string                $fhirName,
                                null|SimpleXMLElement $sourceSXE = null,
                                string                $sourceFilename = '')
    {
        if ('' === ($fhirName = trim($fhirName))) {
            throw new DomainException('$fhirName must be defined');
        }
        $this->_version = $version;
        $this->_fhirName = $fhirName;
        $this->_sourceSXE = $sourceSXE;
        $this->_sourceFilename = $sourceFilename;
        $this->_properties = new Properties($this);
        $this->_enumeration = new Enumeration();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $vars = get_object_vars($this);
        unset($vars['_version']);
        return $vars;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig(): Config
    {
        return $this->_version->getConfig();
    }

    /**
     * @return \DCarbone\PHPFHIR\Version
     */
    public function getVersion(): Version
    {
        return $this->_version;
    }

    /**
     * @return string
     */
    public function getFHIRName(): string
    {
        return $this->_fhirName;
    }

    /**
     * @return \DCarbone\PHPFHIR\Builder\Imports
     */
    public function getImports(): Imports
    {
        // TODO: implement "extraction done" mechanic
        if (!isset($this->_imports)) {
            $this->_imports = new Imports(
                $this->_version->getConfig(),
                $this->getFullyQualifiedNamespace(false),
                $this->getClassName(),
            );
        }
        return $this->_imports;
    }

    /**
     * @param bool $withConstClass
     * @param string $prefix
     * @return string
     */
    public function getConstName(bool $withConstClass, string $prefix = ''): string
    {
        if ($withConstClass) {
            $cn = sprintf('%s::', PHPFHIR_VERSION_CLASSNAME_VERSION_CONSTANTS);
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
        return $this->_kind;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum $kind
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setKind(TypeKindEnum $kind): Type
    {
        if (isset($this->_kind) && $this->_kind !== $kind) {
            throw new LogicException(
                sprintf(
                    'Cannot overwrite Type % s Kind from %s to %s',
                    $this->getFHIRName(),
                    $this->_kind->value,
                    $kind->value
                )
            );
        }
        $this->_kind = $kind;
        return $this;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setPrimitiveType(PrimitiveTypeEnum $primitiveType): Type
    {
        if (isset($this->_primitiveType) && $this->_primitiveType === $primitiveType) {
            throw new LogicException(
                sprintf(
                    'Cannot overwrite Type "%s" PrimitiveType from "%s" to "%s"',
                    $this->getFHIRName(),
                    $this->_primitiveType->value,
                    $primitiveType->value
                )
            );
        }
        $this->_primitiveType = $primitiveType;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        $bits = [];
        foreach ($this->getParentTypes() as $parent) {
            array_unshift($bits, $parent->getClassName());
        }
        if ($ctype = $this->getComponentOfType()) {
            $bits[] = $ctype->getClassName();
        }
        if ([] === $bits) {
            return '';
        }
        return implode(PHPFHIR_NAMESPACE_SEPARATOR, $bits);
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        if (!isset($this->_className)) {
            $this->_className = NameUtils::getTypeClassName($this->getFHIRName());
        }
        return $this->_className;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace(bool $leadingSlash): string
    {
        return $this
            ->getVersion()
            ->getFullyQualifiedName(
                $leadingSlash,
                PHPFHIR_NAMESPACE_TYPES,
                $this->getNamespace(),
            );
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassName(bool $leadingSlash): string
    {
        return $this
            ->getVersion()
            ->getFullyQualifiedName(
                $leadingSlash,
                PHPFHIR_NAMESPACE_TYPES,
                $this->getNamespace(),
                $this->getClassName(),
            );
    }

    /**
     * @return string
     */
    public function getTestClassName(): string
    {
        return sprintf('%sTest', $this->getClassName());
    }

    public function getFullyQualifiedTestNamespace(bool $leadingSlash): string
    {
        return $this
            ->getVersion()
            ->getFullyQualifiedTestsName(
                $leadingSlash,
                PHPFHIR_NAMESPACE_TYPES,
                $this->getNamespace(),
            );
    }

    public function getFullyQualifiedTestClassname(bool $leadingSlash): string
    {
        return $this
            ->getVersion()
            ->getFullyQualifiedTestsName(
                $leadingSlash,
                PHPFHIR_NAMESPACE_TYPES,
                $this->getNamespace(),
                $this->getClassName(),
            );
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Properties
     */
    public function getProperties(): Properties
    {
        return $this->_properties;
    }

    public function getParentProperty(string $name): null|Property
    {
        foreach ($this->getParentPropertiesIterator() as $property) {
            if ($property->getName() === $name) {
                return $property;;
            }
        }
        return null;
    }

    /**
     * Returns true if this type has any locally defined properties.
     *
     * @return bool
     */
    public function hasLocalProperties(): bool
    {
        return 0 !== count($this->_properties);
    }

    /**
     * @return bool
     */
    public function hasPropertiesWithValidations(): bool
    {
        if ($this->isEnumerated()) {
            return true;
        }
        foreach ($this->getProperties()->getIterator() as $property) {
            if ([] !== $property->buildValidationMap($this)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns an indexed iterator containing all properties defined on this type and all parent types.
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getAllPropertiesIndexedIterator(): iterable
    {
        $p = [];
        foreach ($this->getRootFirstParentTypes() as $parentType) {
            foreach ($parentType->getProperties()->getIterator() as $property) {
                $p[$property->getName()] = $property;
            }
        }
        foreach ($this->getProperties()->getIterator() as $property) {
            $p[$property->getName()] = $property;
        }
        // this returns an \SplFixedArray to provide an indexed iterator
        return \SplFixedArray::fromArray($p, preserveKeys: false);
    }

    /**
     * Returns an indexed iterator containing all properties defined on parents of this type.  Locally overloaded
     * properties are omitted.
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Property[]
     */
    public function getParentPropertiesIterator(): iterable
    {
        $p = [];
        foreach ($this->getRootFirstParentTypes() as $parentType) {
            foreach ($parentType->getProperties()->getIterator() as $property) {
                // do not include properties that are overloaded by this type
                if (!$this->_properties->hasProperty($property->getName()) && !isset($p[$property->getName()])) {
                    $p[$property->getName()] = $property;
                }
            }
        }
        return \SplFixedArray::fromArray($p, preserveKeys: false);
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
     * @return \DCarbone\PHPFHIR\Version\Definition\Type[]
     */
    public function getRootFirstParentTypes(): array
    {
        return array_reverse($this->getParentTypes());
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
        return $this->_parentType;
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
        $this->_parentType = $type;
        $this->setParentTypeName($type->getFHIRName());
        return $this;
    }

    /**
     * @return null|string
     */
    public function getParentTypeName(): null|string
    {
        return $this->_parentTypeName;
    }

    /**
     * @param string|null $parentTypeName
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setParentTypeName(null|string $parentTypeName): Type
    {
        $this->_parentTypeName = $parentTypeName;
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
    public function isPrimitiveOrListType(): bool
    {
        return $this->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST);
    }

    /**
     * @return bool
     */
    public function hasPrimitiveOrListParent(): bool
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->isPrimitiveOrListType()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isPrimitiveContainer(): bool
    {
        return $this->_primitiveContainer;
    }

    /**
     * @param bool $primitiveContainer
     * @return $this
     */
    public function setPrimitiveContainer(bool $primitiveContainer): Type
    {
        $this->_primitiveContainer = $primitiveContainer;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasPrimitiveContainerParent(): bool
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->isPrimitiveContainer()) {
                return true;
            }
        }
        return false;
    }

    /**
     * TODO: this is a really poor way to implement this...
     *
     * @return bool
     */
    public function isResourceType(): bool
    {
        return $this->_fhirName === 'Resource'
            || $this->_fhirName === 'DomainResource'
            || str_contains($this->getFullyQualifiedNamespace(false), '\\FHIRResource\\')
            || str_contains($this->getFullyQualifiedNamespace(false), '\\FHIRDomainResource\\');
    }

    public function hasResourceTypeParent(): bool
    {
        return $this->hasParent() && $this->_parentType->isResourceType();
    }

    /**
     * @return int
     */
    public function getMinLength(): int
    {
        return $this->_minLength;
    }

    /**
     * @param int $minLength
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setMinLength(int $minLength): Type
    {
        $this->_minLength = $minLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength(): int
    {
        return $this->_maxLength;
    }

    /**
     * @param int $maxLength
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setMaxLength(int $maxLength): Type
    {
        $this->_maxLength = $maxLength;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPattern(): null|string
    {
        return $this->_pattern;
    }

    /**
     * @param string|null $pattern
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setPattern(null|string $pattern): Type
    {
        $this->_pattern = $pattern;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getComponentOfType(): null|Type
    {
        return $this->_componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setComponentOfType(Type $type): Type
    {
        $this->_componentOfType = $type;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Enumeration
     */
    public function getEnumeration(): Enumeration
    {
        return $this->_enumeration;
    }

    /**
     * @param mixed $enumValue
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function addEnumerationValue(EnumerationValue $enumValue): Type
    {
        $this->_enumeration->addValue($enumValue);
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
        return $this->_primitiveType;
    }

    /**
     * @return bool
     */
    public function hasPrimitiveType(): bool
    {
        return isset($this->_primitiveType);
    }

    /**
     * @return array
     */
    public function getUnionOf(): array
    {
        return $this->_unionOf;
    }

    /**
     * @param array $unionOf
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setUnionOf(array $unionOf): Type
    {
        $this->_unionOf = $unionOf;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRestrictionBaseFHIRName(): null|string
    {
        return $this->_restrictionBaseFHIRName;
    }

    /**
     * @param string $restrictionBaseFHIRName
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setRestrictionBaseFHIRName(string $restrictionBaseFHIRName): Type
    {
        $this->_restrictionBaseFHIRName = $restrictionBaseFHIRName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getRestrictionBaseFHIRType(): null|Type
    {
        return $this->_restrictionBaseFHIRType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setRestrictionBaseFHIRType(Type $type): Type
    {
        $this->_restrictionBaseFHIRType = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMixed(): bool
    {
        return $this->_mixed;
    }

    /**
     * @param bool $mixed
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setMixed(bool $mixed): Type
    {
        $this->_mixed = $mixed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContainedType(): bool
    {
        return $this->_containedType;
    }

    /**
     * @param bool $containedType
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function setContainedType(bool $containedType): Type
    {
        $this->_containedType = $containedType;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasContainedTypeParent(): bool
    {
        return $this->hasParent() && $this->_parentType->isContainedType();
    }

    /**
     * @return bool
     */
    public function isQuantity(): bool
    {
        return $this->_fhirName === 'Quantity';
    }

    /**
     * @return bool
     */
    public function hasQuantityParent(): bool
    {
        foreach ($this->getParentTypes() as $parent) {
            if ($parent->isQuantity()) {
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
        $coreFiles = $this->_version->getConfig()->getCoreFiles();
        $versionCoreFiles = $this->_version->getCoreFiles();
        $sourceMeta = $this->_version->getSourceMetadata();

        // dstu1 has its own special type interface
        if ($sourceMeta->isDSTU1() && !$this->isPrimitiveOrListType() && !$this->hasPrimitiveOrListParent()) {
            if (!$this->hasConcreteParent()) {
                $interfaces[PHPFHIR_TYPES_INTERFACE_DSTU1_TYPE] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_DSTU1_TYPE)
                    ->getFullyQualifiedNamespace(false);
            }
        }

        // first, determine which base type interface it must implement
        if ($this->isPrimitiveOrListType()) {
            if (!$this->hasPrimitiveOrListParent()) {
                $interfaces[PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE)
                    ->getFullyQualifiedNamespace(false);
            }
        } else if ($this->isPrimitiveContainer()) {
            if (!$this->hasPrimitiveContainerParent()) {
                if ($sourceMeta->isDSTU1()) {
                    $interfaces[PHPFHIR_TYPES_INTERFACE_DSTU1_PRIMITIVE_CONTAINER_TYPE] = $coreFiles
                        ->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_CONTAINER_TYPE)
                        ->getFullyQualifiedNamespace(false);
                } else {
                    $interfaces[PHPFHIR_TYPES_INTERFACE_PRIMITIVE_CONTAINER_TYPE] = $coreFiles
                        ->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_CONTAINER_TYPE)
                        ->getFullyQualifiedNamespace(false);
                }
            }
        } else if ($this->isResourceType() || $this->getKind()->isResourceContainer($this->_version)) {
            if (!$this->hasResourceTypeParent() && !$sourceMeta->isDSTU1()) {
                $interfaces[PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE)
                    ->getFullyQualifiedNamespace(false);
            }
        } else if (!$this->hasParent() && !$sourceMeta->isDSTU1() && !$this->isAbstract()) {
            $interfaces[PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE] = $coreFiles
                ->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE)
                ->getFullyQualifiedNamespace(false);
        }

        // comment container types
        if ($this->isCommentContainer() && !$this->hasCommentContainerParent()) {
            $interfaces[PHPFHIR_TYPES_INTERFACE_COMMENT_CONTAINER] = $coreFiles
                ->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_COMMENT_CONTAINER)
                ->getFullyQualifiedNamespace(false);
        }

        // types that can be contained within a resource container type
        if ($this->isContainedType() && !$this->hasContainedTypeParent()) {
            $interfaces[PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE] = $versionCoreFiles
                ->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE)
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
        $sourceMeta = $this->_version->getSourceMetadata();
        $parentType = $this->getParentType();
        $coreFiles = $this->_version->getConfig()->getCoreFiles();

        if (!$this->hasConcreteParent()) {
            // if this type has no parent(s), try to add all traits

            if ($this->isCommentContainer() && !$this->hasCommentContainerParent()) {
                $traits[PHPFHIR_TYPES_TRAIT_COMMENT_CONTAINER] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_TYPES_TRAIT_COMMENT_CONTAINER)
                    ->getFullyQualifiedNamespace(false);
            }

            // these must only be added if the type has local properties
            if (($this->isResourceType() || $sourceMeta->isDSTU1() || $this->_kind->isResourceContainer($this->_version)) && $this->hasLocalProperties()) {
                $traits[PHPFHIR_TRAIT_SOURCE_XMLNS] = $coreFiles
                    ->getCoreFileByEntityName(PHPFHIR_TRAIT_SOURCE_XMLNS)
                    ->getFullyQualifiedNamespace(false);
            }
        } else if ($this->isResourceType() && !$parentType->hasLocalProperties()) {
            // if this type _does_ have a parent, only add these traits if the parent does not have local properties
            $traits[PHPFHIR_TRAIT_SOURCE_XMLNS] = $coreFiles
                ->getCoreFileByEntityName(PHPFHIR_TRAIT_SOURCE_XMLNS)
                ->getFullyQualifiedNamespace(false);
        }

        // we do not apply the value container trait to primitive types, as they have a per-type implementation
        // of _getFormattedValue()
        if (($this->isPrimitiveContainer() && !$this->hasPrimitiveContainerParent())) {
            $traits[PHPFHIR_TYPES_TRAIT_VALUE_CONTAINER] = $coreFiles
                ->getCoreFileByEntityName(PHPFHIR_TYPES_TRAIT_VALUE_CONTAINER)
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
        $this->_commentContainer = $commentContainer;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCommentContainer(): bool
    {
        return $this->_commentContainer;
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