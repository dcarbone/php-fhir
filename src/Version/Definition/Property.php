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

use DCarbone\PHPFHIR\Enum\PropertyUseEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;

class Property
{
    use DocumentationTrait,
        SourceTrait;

    private Type $_memberOf;
    private Property $_overloads;

    private null|string $_name = null;
    private null|string $_ref = null;

    private null|string $valueFHIRTypeName = null;
    private ?Type $valueFHIRType = null;
    private null|string $rawPHPValue = null;

    private int $minOccurs = 0;
    private null|int $maxOccurs = null;

    private null|string $pattern = null;

    private PropertyUseEnum $use;

    private null|string $fixed = null; // TODO: what the hell is this...?

    private null|string $namespace = null;// NOTE: not a php namespace


    public function __construct(Type                   $memberOf,
                                \SimpleXMLElement      $sxe,
                                string                 $sourceFilename,
                                string                 $name = '',
                                string                 $ref = '',
                                string|PropertyUseEnum $use = PropertyUseEnum::OPTIONAL,
                                string|int             $minOccurs = 0,
                                string|int             $maxOccurs = '',
                                string                 $valueFHIRTypeName = '',
                                string                 $fixed = '',
                                string                 $namespace = '')
    {
        if ('' === $name && '' === $ref) {
            throw new \InvalidArgumentException(sprintf(
                'At least one of $name or $ref must be provided for new property on type %s: %s',
                $memberOf->getFHIRName(),
                $sxe->saveXML(),
            ));
        }

        $this->_memberOf = $memberOf;
        $this->sourceSXE = $sxe;
        $this->sourceFilename = $sourceFilename;

        if ('' !== $name) {
            $this->_name = $name;
        }
        if ('' !== $ref) {
            $this->_ref = $ref;
        }

        $this->setUse($use);
        $this->setMinOccurs($minOccurs);
        $this->setMaxOccurs($maxOccurs);
        $this->setValueFHIRTypeName($valueFHIRTypeName);
        $this->setFixed($fixed);
        $this->setNamespace($namespace);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'ref' => $this->getRef(),
            'fhirTypeName' => $this->getValueFHIRTypeName(),
            'sourceSXE' => $this->getSourceSXE(),
            'minOccurs' => $this->getMinOccurs(),
            'maxOccurs' => $this->getMaxOccurs(),
            'pattern' => $this->getPattern(),
            'rawPHPValue' => $this->getRawPHPValue(),
            'fhirType' => (string)$this->getValueFHIRType(),
            'use' => $this->getUse()->value,
            'fixed' => (string)$this->getFixed(),
            'namespace' => (string)$this->getNamespace(),
        ];
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function getMemberOf(): Type
    {
        return $this->_memberOf;
    }

    /**
     * @return string|null
     */
    public function getName(): null|string
    {
        return $this->_name;
    }

    public function setName(string $name): self
    {
        if ('' === $name) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Type "%s" Property $name cannot be empty',
                    $this->valueFHIRType->getFHIRName()
                )
            );
        }
        $this->_name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExtName(): null|string
    {
        if (null === $this->_name) {
            return null;
        }
        return "_{$this->_name}";
    }

    /**
     * @return string|null
     */
    public function getValueFHIRTypeName(): null|string
    {
        return $this->valueFHIRTypeName;
    }

    /**
     * @param string $valueFHIRTypeName
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setValueFHIRTypeName(string $valueFHIRTypeName): Property
    {
        if ('' === $valueFHIRTypeName) {
            $valueFHIRTypeName = null;
        }
        $this->valueFHIRTypeName = $valueFHIRTypeName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getValueFHIRType(): ?Type
    {
        return $this->valueFHIRType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $valueFHIRType
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setValueFHIRType(Type $valueFHIRType): Property
    {
        $this->valueFHIRType = $valueFHIRType;
        $this->valueFHIRTypeName = $valueFHIRType->getFHIRName();
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRawPHPValue(): null|string
    {
        return $this->rawPHPValue;
    }

    /**
     * @param string|null $rawPHPValue
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setRawPHPValue(?string $rawPHPValue): Property
    {
        $this->rawPHPValue = $rawPHPValue;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getMaxOccurs(): null|int
    {
        return $this->maxOccurs;
    }

    /**
     * @param int|string $maxOccurs
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setMaxOccurs(int|string $maxOccurs): Property
    {
        if (is_string($maxOccurs)) {
            $maxOccurs = match ($maxOccurs) {
                'unbounded' => PHPFHIR_UNLIMITED,
                '' => null,
                default => intval($maxOccurs),
            };
        }
        $this->maxOccurs = intval($maxOccurs);
        return $this;
    }

    /**
     * @return int
     */
    public function getMinOccurs(): int
    {
        return $this->minOccurs;
    }

    /**
     * @param string|int $minOccurs
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setMinOccurs(string|int $minOccurs): Property
    {
        if (is_string($minOccurs)) {
            $minOccurs = match ($minOccurs) {
                '' => 0,
                default => intval($minOccurs),
            };
        }
        $this->minOccurs = $minOccurs;
        return $this;
    }

    /**
     * If defined, this is a regex pattern by which to validate the contents.
     *
     * @return null|string
     */
    public function getPattern(): null|string
    {
        if (isset($this->pattern)) {
            return $this->pattern;
        }
        if (isset($this->valueFHIRType)) {
            return $this->valueFHIRType->getPattern();
        }
        return null;
    }

    /**
     * @param string $pattern
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setPattern(string $pattern): Property
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCollection(): bool
    {
        $maxOccurs = $this->getMaxOccurs();
        return null !== $maxOccurs && (PHPFHIR_UNLIMITED === $maxOccurs || 1 < $maxOccurs);
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\PropertyUseEnum
     */
    public function getUse(): PropertyUseEnum
    {
        return $this->use;
    }

    /**
     * @param string|\DCarbone\PHPFHIR\Enum\PropertyUseEnum $use
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setUse(string|PropertyUseEnum $use): Property
    {
        if (is_string($use)) {
            $use = match ($use) {
                '' => PropertyUseEnum::OPTIONAL,
                default => PropertyUseEnum::from($use),
            };
        }
        $this->use = $use;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRef(): null|string
    {
        return $this->_ref;
    }

    /**
     * @param string $ref
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setRef(string $ref): Property
    {
        if ('' === $ref) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Type "%s" Property $ref cannot be empty',
                    $this->valueFHIRType->getFHIRName()
                )
            );
        }
        $this->_ref = $ref;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFixed(): null|string
    {
        return $this->fixed;
    }

    /**
     * @param string|null $fixed
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setFixed(?string $fixed): Property
    {
        if ('' === $fixed) {
            $fixed = null;
        }
        $this->fixed = $fixed;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNamespace(): null|string
    {
        return $this->namespace;
    }

    /**
     * @param string|null $namespace
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setNamespace(?string $namespace): Property
    {
        if ('' === $namespace) {
            $namespace = null;
        }
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldConstantName(): string
    {
        return 'FIELD_' . NameUtils::getConstName($this->getName());
    }

    /**
     * @return string
     */
    public function getFieldConstantExtensionName(): string
    {
        return $this->getFieldConstantName() . '_EXT';
    }

    /**
     * @return bool
     */
    public function isValueProperty(): bool
    {
        return PHPFHIR_VALUE_PROPERTY_NAME === $this->getName();
    }

    /**
     * @return string
     */
    public function getSetterName(): string
    {
        return ($this->isCollection() ? 'add' : 'set') . ucfirst($this->getName());
    }

    /**
     * @return string
     */
    public function getGetterName(): string
    {
        return sprintf('get%s', ucfirst($this->getName()));
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $local
     * @return array
     */
    public function buildValidationMap(Type $local): array
    {
        $map = [];
        $memberOf = $this->getMemberOf();

        $pattern = $this->getPattern();
        if (null === $pattern) {
            $pattern = $memberOf->getPattern();
        }

        if (null !== $pattern) {
            $map[PHPFHIR_VALIDATION_PATTERN_NAME] = '/^' . addcslashes($pattern, '/\'') . '$/';
        }

        $minOccurs = $this->getMinOccurs();
        $maxOccurs = $this->getMaxOccurs();
        $minLength = $memberOf->getMinLength();
        $maxlength = $memberOf->getMaxLength();

        if (0 < $minOccurs) {
            $map[PHPFHIR_VALIDATION_MIN_OCCURS_NAME] = $minOccurs;
        }
        if (null !== $maxOccurs && PHPFHIR_UNLIMITED !== $maxOccurs && 1 < $maxOccurs) {
            $map[PHPFHIR_VALIDATION_MAX_OCCURS_NAME] = $maxOccurs;
        }

        if (0 < $minLength) {
            $map[PHPFHIR_VALIDATION_MIN_LENGTH_NAME] = $minLength;
        }
        if (PHPFHIR_UNLIMITED !== $maxlength) {
            $map[PHPFHIR_VALIDATION_MAX_LENGTH_NAME] = $maxlength;
        }

        if ($memberOf->isEnumerated()) {
            $map[PHPFHIR_VALIDATION_ENUM_NAME] = [];
            foreach ($memberOf->getEnumeration()->getGenerator() as $enum) {
                $map[PHPFHIR_VALIDATION_ENUM_NAME][] = $enum->getValue();
            }
        } else if ($this->isValueProperty() && $local->isEnumerated()) {
            $map[PHPFHIR_VALIDATION_ENUM_NAME] = [];
            foreach ($local->getEnumeration()->getGenerator() as $enum) {
                $map[PHPFHIR_VALIDATION_ENUM_NAME][] = $enum->getValue();
            }
        }

        return $map;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Property $property
     * @return $this
     */
    public function setOverloadedProperty(Property $property): Property
    {
        $this->_overloads = $property;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Property|null
     */
    public function getOverloadedProperty(): null|Property
    {
        return $this->_overloads ?? null;
    }

    /**
     * This method will panic if this is a primitive type.  Deal with it, also make it better later.
     *
     * @return bool
     */
    public function requiresXMLLocation(): bool
    {
        return $this->_memberOf->hasPrimitiveParent()
            || $this->_memberOf->getKind()->isOneOf(
                TypeKindEnum::PRIMITIVE_CONTAINER,
                TypeKindEnum::PRIMITIVE,
                TypeKindEnum::LIST,
            );
    }

    /**
     * @return string
     */
    public function defaultXMLLocationEnumValue(): string
    {
        if (!$this->requiresXMLLocation()) {
            throw new \RuntimeException(sprintf(
                'Type "%s" property "%s" does not support XML location setting',
                $this->getMemberOf()->getFHIRName(),
                $this->getName(),
            ));
        }

        return $this->_memberOf->isValueContainer() ? 'ELEMENT' : 'ATTRIBUTE';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($n = $this->getName()) {
            return $n;
        } elseif ($n = $this->getRef()) {
            return $n;
        } else {
            return 'UNKNOWN';
        }
    }
}