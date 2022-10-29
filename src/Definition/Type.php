<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;
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

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private VersionConfig $config;

    /**
     * The raw name of the FHIR element this type was created from
     * @var string
     */
    private string $fhirName;

    /** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum|null */
    private ?TypeKindEnum $kind = null;

    /** @var string */
    private string $className;

    /** @var \DCarbone\PHPFHIR\Definition\Properties */
    private Properties $properties;

    /** @var null|string */
    private ?string $parentTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private ?Type $parentType = null;

    /** @var int */
    private int $minLength = 0;
    /** @var int */
    private int $maxLength = PHPFHIR_UNLIMITED;
    /** @var null|string */
    private ?string $pattern = null;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private ?Type $componentOfType = null;

    /** @var \DCarbone\PHPFHIR\Definition\Enumeration */
    private Enumeration $enumeration;

    /** @var array */
    private array $unionOf = [];

    /** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum */
    private PrimitiveTypeEnum $primitiveType;

    /** @var null|string */
    private ?string $restrictionBaseFHIRName = null;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private ?Type $restrictionBaseFHIRType = null;

    /** @var bool */ // TODO: what the hell is this...?
    private bool $mixed = false;

    /** @var bool */
    private bool $containedType = false;
    /** @var bool */
    private bool $valueContainer = false;
    /** @var bool */
    private bool $commentContainer = false;

    /** @var \DCarbone\PHPFHIR\Definition\TypeImports */
    private TypeImports $imports;

    /**
     * Type constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param string $fhirName
     * @param \SimpleXMLElement|null $sourceSXE
     * @param string $sourceFilename
     */
    public function __construct(
        VersionConfig $config,
        string $fhirName,
        SimpleXMLElement $sourceSXE = null,
        string $sourceFilename = ''
    ) {
        if ('' === ($fhirName = trim($fhirName))) {
            throw new DomainException('$fhirName must be defined');
        }
        $this->config = $config;
        $this->fhirName = $fhirName;
        $this->sourceSXE = $sourceSXE;
        $this->sourceFilename = $sourceFilename;
        $this->properties = new Properties($config, $this);
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
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig(): VersionConfig
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getFHIRName(): string
    {
        return $this->fhirName;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\TypeImports
     */
    public function getImports(): TypeImports
    {
        return $this->imports;
    }

    /**
     * @param bool $withClass
     * @param string $prefix
     * @return string
     */
    public function getConstName(bool $withClass, string $prefix = ''): string
    {
        return ($withClass ? PHPFHIR_CLASSNAME_CONSTANTS . '::' : '') . strtoupper($prefix) . NameUtils::getConstName(
                $this->getFHIRName()
            );
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
    public function getKind(): ?TypeKindEnum
    {
        return $this->kind;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum $kind
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setKind(TypeKindEnum $kind): Type
    {
        if (isset($this->kind) && !$this->kind->equals($kind)) {
            throw new LogicException(
                sprintf(
                    'Cannot overwrite Type % s Kind from %s to %s',
                    $this->getFHIRName(),
                    $this->kind,
                    $kind
                )
            );
        }
        $this->kind = $kind;
        return $this;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setPrimitiveType(PrimitiveTypeEnum $primitiveType): Type
    {
        if (isset($this->primitiveType) && $this->primitiveType->equals($primitiveType)) {
            throw new LogicException(
                sprintf(
                    'Cannot overwrite Type "%s" PrimitiveType from "%s" to "%s"',
                    $this->getFHIRName(),
                    $this->primitiveType,
                    $primitiveType
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
        if ($this->isRootType()) {
            return '';
        }
        $ns = [];
        foreach ($this->getParentTypes() as $parent) {
            array_unshift($ns, $parent->getClassName());
        }
        if ($ctype = $this->getComponentOfType()) {
            $ns[] = $ctype->getClassName();
        }
        return implode('\\', $ns);
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
        $ns = $this->getConfig()->getNamespace(false);
        $typeNS = $this->getTypeNamespace();
        if ('' !== $typeNS) {
            $ns = "{$ns}\\{$typeNS}";
        }
        return $leadingSlash ? "\\{$ns}" : $ns;
    }

    /**
     * @param $testType
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedTestNamespace($testType, bool $leadingSlash): string
    {
        $ns = $this->getConfig()->getTestsNamespace($testType, false);
        $typeNS = $this->getTypeNamespace();
        if ('' !== $typeNS) {
            $ns = "{$ns}\\{$typeNS}";
        }
        return $leadingSlash ? "\\{$ns}" : $ns;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassName(bool $leadingSlash): string
    {
        $cn = $this->getFullyQualifiedNamespace(false);
        if ('' === $cn) {
            $cn = $this->getClassName();
        } else {
            $cn .= "\\{$this->getClassName()}";
        }
        return $leadingSlash ? "\\{$cn}" : $cn;
    }

    /**
     * @return string
     */
    public function getTestClassName(): string
    {
        return "{$this->getClassName()}Test";
    }

    /**
     * @param $testType
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedTestClassName($testType, bool $leadingSlash): string
    {
        $ns = $this->getFullyQualifiedTestNamespace($testType, false);
        if ('' === $ns) {
            $cn = $this->getTestClassName();
        } else {
            $cn = "{$ns}\\{$this->getTestClassName()}";
        }
        return $leadingSlash ? "\\{$cn}" : $ns;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Properties
     */
    public function getProperties(): Properties
    {
        return $this->properties;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Property[]
     */
    public function getAllPropertiesIterator(): iterable
    {
        $properties = [];
        foreach($this->getProperties()->getDirectIterator() as $property) {
            $properties[$property->getName()] = $property;
        }
        foreach($this->getParentTypes() as $parentType) {
            foreach($parentType->getAllPropertiesIterator() as $property) {
                if (!isset($properties[$property->getName()])) {
                    $properties[$property->getName()] = $property;
                }
            }
        }
        return \SplFixedArray::fromArray($properties, false);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type[]
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
     * @return \DCarbone\PHPFHIR\Definition\Type
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
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getParentType(): ?Type
    {
        return $this->parentType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
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
    public function getParentTypeName(): ?string
    {
        return $this->parentTypeName;
    }

    /**
     * @param string|null $parentTypeName
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setParentTypeName(?string $parentTypeName): Type
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
    public function hasResourceParent(): bool
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->getKind()->isResource()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasPrimitiveParent(): bool
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->getKind()->isPrimitive()) {
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
            if ($parentType->getKind()->isPrimitiveContainer()) {
                return true;
            }
        }
        return false;
    }

    /**
     * TODO: super hacky.
     *
     * @return bool
     */
    public function isDomainResource(): bool
    {
        return false !== strpos($this->getFullyQualifiedNamespace(false), 'DomainResource');
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
     * @return \DCarbone\PHPFHIR\Definition\Type
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
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMaxLength(int $maxLength): Type
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    /**
     * @param string|null $pattern
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setPattern(?string $pattern): Type
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getComponentOfType(): ?Type
    {
        return $this->componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setComponentOfType(Type $type): Type
    {
        $this->componentOfType = $type;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Enumeration
     */
    public function getEnumeration(): Enumeration
    {
        return $this->enumeration;
    }

    /**
     * @param mixed $enumValue
     * @return \DCarbone\PHPFHIR\Definition\Type
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
     * @return array
     */
    public function getUnionOf(): array
    {
        return $this->unionOf;
    }

    /**
     * @param array $unionOf
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setUnionOf(array $unionOf): Type
    {
        $this->unionOf = $unionOf;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRestrictionBaseFHIRName(): ?string
    {
        return $this->restrictionBaseFHIRName;
    }

    /**
     * @param string $restrictionBaseFHIRName
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setRestrictionBaseFHIRName(string $restrictionBaseFHIRName): Type
    {
        $this->restrictionBaseFHIRName = $restrictionBaseFHIRName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getRestrictionBaseFHIRType(): ?Type
    {
        return $this->restrictionBaseFHIRType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
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
     * @return \DCarbone\PHPFHIR\Definition\Type
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
     * @return \DCarbone\PHPFHIR\Definition\Type
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
     * @return \DCarbone\PHPFHIR\Definition\Type
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
     * @return array
     */
    public function getDirectlyImplementedInterfaces(): array
    {
        $interfaces = [];
        $parentType = $this->getParentType();

        if (null === $parentType) {
            if ($this->isCommentContainer()) {
                $interfaces[] = PHPFHIR_INTERFACE_COMMENT_CONTAINER;
            }
            if ($this->isContainedType()) {
                $interfaces[] = PHPFHIR_INTERFACE_CONTAINED_TYPE;
            } else {
                $interfaces[] = PHPFHIR_INTERFACE_TYPE;
            }
        } elseif ($this->isContainedType() && !$parentType->isContainedType()) {
            $interfaces[] = PHPFHIR_INTERFACE_CONTAINED_TYPE;
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
        if (null === $parentType) {
            if ($this->isCommentContainer()) {
                $traits[] = PHPFHIR_TRAIT_COMMENT_CONTAINER;
            }
            $traits[] = PHPFHIR_TRAIT_VALIDATION_ASSERTIONS;
            $traits[] = PHPFHIR_TRAIT_CHANGE_TRACKING;
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
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setCommentContainer(bool $commentContainer): Type
    {
        $this->commentContainer = (bool)$commentContainer;
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
     * @return string
     */
    public function __toString()
    {
        return $this->getFHIRName();
    }
}