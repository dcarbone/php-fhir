<?php

namespace DCarbone\PHPFHIR\Definition;

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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class Type
 * @package DCarbone\PHPFHIR\Definition
 */
class Type
{
    use DocumentationTrait, SourceTrait;

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;

    /**
     * The raw name of the FHIR element this type was created from
     * @var string
     */
    private $fhirName;

    /** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum */
    private $kind = null;

    /** @var string */
    private $className;

    /** @var \DCarbone\PHPFHIR\Definition\Properties */
    private $properties;

    /** @var null|string */
    private $parentTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $parentType = null;

    /** @var int */
    private $minLength = 0;
    /** @var int */
    private $maxLength = PHPFHIR_UNLIMITED;
    /** @var null|string */
    private $pattern = null;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $componentOfType = null;

    /** @var \DCarbone\PHPFHIR\Definition\Enumeration */
    private $enumeration;

    /** @var array */
    private $unionOf = [];

    /** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum */
    private $primitiveType;

    /** @var null|string */
    private $restrictionBaseFHIRName = null;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $restrictionBaseFHIRType = null;

    /** @var bool */ // TODO: what the hell is this...?
    private $mixed = false;

    /** @var bool */
    private $containedType = false;

    /** @var bool */
    private $valueContainer = false;

    /** @var \DCarbone\PHPFHIR\Definition\TypeImports */
    private $imports;

    /**
     * Type constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param string $fhirName
     * @param \SimpleXMLElement|null $sourceSXE
     * @param string $sourceFilename
     */
    public function __construct(VersionConfig $config,
                                $fhirName,
                                \SimpleXMLElement $sourceSXE = null,
                                $sourceFilename = '')
    {
        if ('' === ($fhirName = trim($fhirName))) {
            throw new \DomainException('$fhirName must be defined');
        }
        $this->config = $config;
        $this->fhirName = $fhirName;
        $this->sourceSXE = $sourceSXE;
        $this->sourceFilename = $sourceFilename;
        $this->properties = new Properties($config, $this);
        $this->enumeration = new Enumeration($this);
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
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getFHIRName()
    {
        return $this->fhirName;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\TypeImports
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * @param bool $withClass
     * @param string $prefix
     * @return string
     */
    public function getConstName($withClass, $prefix = '')
    {
        return ($withClass ? PHPFHIR_CLASSNAME_CONSTANTS . '::' : '') . strtoupper($prefix) . NameUtils::getConstName($this->getFHIRName());
    }

    /**
     * @param bool $withClass
     * @return string
     */
    public function getTypeNameConst($withClass)
    {
        return $this->getConstName($withClass, 'TYPE_NAME_');
    }

    /**
     * @param bool $withClass
     * @return string
     */
    public function getClassNameConst($withClass)
    {
        return $this->getConstName($withClass, 'TYPE_CLASS_');
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\TypeKindEnum
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TypeKindEnum $kind
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setKind(TypeKindEnum $kind)
    {
        if (isset($this->kind) && !$this->kind->equals($kind)) {
            throw new \LogicException(sprintf(
                'Cannot overwrite Type % s Kind from %s to %s',
                $this->getFHIRName(),
                $this->kind,
                $kind
            ));
        }
        $this->kind = $kind;
        return $this;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setPrimitiveType(PrimitiveTypeEnum $primitiveType)
    {
        if (isset($this->primitiveType) && $this->primitiveType->equals($primitiveType)) {
            throw new \LogicException(sprintf(
                'Cannot overwrite Type "%s" PrimitiveType from "%s" to "%s"',
                $this->getFHIRName(),
                $this->primitiveType,
                $primitiveType
            ));
        }
        $this->primitiveType = $primitiveType;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeNamespace()
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
    public function getClassName()
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
    public function getFullyQualifiedNamespace($leadingSlash)
    {
        $ns = $this->getConfig()->getNamespace(false);
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
    public function getFullyQualifiedTestNamespace($leadingSlash)
    {
        $ns = $this->getConfig()->getTestsNamespace(false);
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
    public function getFullyQualifiedClassName($leadingSlash)
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
    public function getTestClassName()
    {
        return "{$this->getClassName()}Test";
    }

    /**
     * @param boolean $leadingSlash
     * @return string
     */
    public function getFullyQualifiedTestClassName($leadingSlash)
    {
        $ns = $this->getFullyQualifiedTestNamespace(false);
        if ('' === $ns) {
            $cn = $this->getTestClassName();
        } else {
            $cn = "{$ns}\\{$this->getTestClassName()}";
        }
        return $leadingSlash ? "\\{$cn}" : $ns;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function addProperty(Property $property)
    {
        $this->properties->addProperty($property);
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Properties
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getParentTypes()
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
    public function isRootType()
    {
        return null === $this->getParentType();
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function getRootType()
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
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setParentType(Type $type)
    {
        $this->parentType = $type;
        $this->setParentTypeName($type->getFHIRName());
        return $this;
    }

    /**
     * @return null|string
     */
    public function getParentTypeName()
    {
        return $this->parentTypeName;
    }

    /**
     * @param string|null $parentTypeName
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setParentTypeName($parentTypeName)
    {
        $this->parentTypeName = $parentTypeName;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return null !== $this->getParentTypeName() || null !== $this->getParentType();
    }

    /**
     * @return bool
     */
    public function hasResourceParent()
    {
        foreach ($this->getParentTypes() as $parentType) {
            $kind = $parentType->getKind();
            if ($kind->isResource()) {
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
    public function isDomainResource()
    {
        return false !== strpos($this->getFullyQualifiedNamespace(false), 'DomainResource');
    }

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @param int $minLength
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMinLength($minLength)
    {
        if (!is_int($minLength)) {
            throw new \InvalidArgumentException(sprintf(
                '$minLength must be int, %s seen',
                gettype($minLength)
            ));
        }
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMaxLength($maxLength)
    {
        if (!is_int($maxLength)) {
            throw new \InvalidArgumentException(sprintf(
                '$maxLength must be int, %s seen',
                gettype($maxLength)
            ));
        }
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string|null $pattern
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getComponentOfType()
    {
        return $this->componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setComponentOfType(Type $type)
    {
        $this->componentOfType = $type;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Enumeration
     */
    public function getEnumeration()
    {
        return $this->enumeration;
    }

    /**
     * @param mixed $enumValue
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function addEnumerationValue(EnumerationValue $enumValue)
    {
        $this->enumeration->addValue($enumValue);
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum
     */
    public function getPrimitiveType()
    {
        return $this->primitiveType;
    }

    /**
     * @return array
     */
    public function getUnionOf()
    {
        return $this->unionOf;
    }

    /**
     * @param array $unionOf
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setUnionOf(array $unionOf)
    {
        $this->unionOf = $unionOf;
        return $this;
    }

    /**
     * @return string
     */
    public function getRestrictionBaseFHIRName()
    {
        return $this->restrictionBaseFHIRName;
    }

    /**
     * @param string $restrictionBaseFHIRName
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setRestrictionBaseFHIRName($restrictionBaseFHIRName)
    {
        $this->restrictionBaseFHIRName = $restrictionBaseFHIRName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getRestrictionBaseFHIRType()
    {
        return $this->restrictionBaseFHIRType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setRestrictionBaseFHIRType(Type $type)
    {
        $this->restrictionBaseFHIRType = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMixed()
    {
        return $this->mixed;
    }

    /**
     * @param bool $mixed
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setMixed($mixed)
    {
        $this->mixed = (bool)$mixed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContainedType()
    {
        return $this->containedType;
    }

    /**
     * @param bool $containedType
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setContainedType($containedType)
    {
        $this->containedType = (bool)$containedType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValueContainer()
    {
        return $this->valueContainer;
    }

    /**
     * @param bool $valueContainer
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function setValueContainer($valueContainer)
    {
        $this->valueContainer = (bool)$valueContainer;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasValueContainerParent()
    {
        foreach ($this->getParentTypes() as $parent) {
            if ($parent->isValueContainer()) {
                return true;
            }
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