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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;
use DCarbone\PHPFHIR\Enum\PropertyUseEnum;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Class Property
 * @package DCarbone\PHPFHIR\Definition
 */
class Property
{
    use DocumentationTrait;
    use SourceTrait;

    /** @var \DCarbone\PHPFHIR\Definition\Type */
    private Type $memberOf;

    /** @var string|null */
    private ?string $name = null;

    /** @var string|null */
    private ?string $valueFHIRTypeName = null;

    /** @var null|string */
    private ?string $rawPHPValue = null;

    /** @var int */
    private int $minOccurs = 0;
    /** @var int */
    private int $maxOccurs = 1;

    /** @var string|null */
    private ?string $pattern = null;

    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private ?Type $valueFHIRType = null;

    /** @var \DCarbone\PHPFHIR\Enum\PropertyUseEnum */
    private PropertyUseEnum $use;

    /** @var string|null */
    private ?string $ref = null;

    /** @var null|string */ // TODO: what the hell is this...?
    private ?string $fixed = null;

    /** @var null|string */ // NOTE: not a php namespace
    private ?string $namespace = null;

    /** @var bool */
    private bool $overloaded = false;

    /**
     * Property constructor.
     * @param \DCarbone\PHPFHIR\Definition\Type $memberOf
     * @param \SimpleXMLElement $sxe
     * @param string $sourceFilename
     */
    public function __construct(Type $memberOf, SimpleXMLElement $sxe, string $sourceFilename)
    {
        $this->memberOf = $memberOf;
        $this->sourceSXE = $sxe;
        $this->sourceFilename = $sourceFilename;
        $this->use = new PropertyUseEnum(PropertyUseEnum::OPTIONAL);
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
            'use' => (string)$this->getUse(),
            'fixed' => (string)$this->getFixed(),
            'namespace' => (string)$this->getNamespace(),
        ];
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function getMemberOf(): Type
    {
        return $this->memberOf;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setName(string $name): Property
    {
        if ('' === $name) {
            throw new InvalidArgumentException(
                sprintf(
                    'Type "%s" Property $name cannot be empty',
                    $this->valueFHIRType->getFHIRName()
                )
            );
        }
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getValueFHIRTypeName(): ?string
    {
        return $this->valueFHIRTypeName;
    }

    /**
     * @param string $valueFHIRTypeName
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setValueFHIRTypeName(string $valueFHIRTypeName): Property
    {
        $this->valueFHIRTypeName = $valueFHIRTypeName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getValueFHIRType(): ?Type
    {
        return $this->valueFHIRType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type $valueFHIRType
     * @return \DCarbone\PHPFHIR\Definition\Property
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
    public function getRawPHPValue(): ?string
    {
        return $this->rawPHPValue;
    }

    /**
     * @param string|null $rawPHPValue
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setRawPHPValue(?string $rawPHPValue): Property
    {
        $this->rawPHPValue = $rawPHPValue;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxOccurs(): int
    {
        return $this->maxOccurs;
    }

    /**
     * @param int|string $maxOccurs
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setMaxOccurs($maxOccurs): Property
    {
        if (is_string($maxOccurs) && 'unbounded' === strtolower($maxOccurs)) {
            $this->maxOccurs = PHPFHIR_UNLIMITED;
        } else {
            $this->maxOccurs = intval($maxOccurs);
        }
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
     * @param int $minOccurs
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setMinOccurs(int $minOccurs): Property
    {
        $this->minOccurs = $minOccurs;
        return $this;
    }

    /**
     * If defined, this is a regex pattern by which to validate the contents.
     *
     * @return null|string
     */
    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     * @return \DCarbone\PHPFHIR\Definition\Property
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
        return PHPFHIR_UNLIMITED === $maxOccurs || 1 < $maxOccurs;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return 1 <= $this->getMinOccurs();
    }

    /**
     * @return bool
     */
    public function unlimitedOccurrences(): bool
    {
        return PHPFHIR_UNLIMITED === $this->getMaxOccurs();
    }

    /**
     * @return \DCarbone\PHPFHIR\Enum\PropertyUseEnum
     */
    public function getUse(): PropertyUseEnum
    {
        return $this->use;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PropertyUseEnum $use
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setUse(PropertyUseEnum $use): Property
    {
        $this->use = $use;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRef(): ?string
    {
        return $this->ref;
    }

    /**
     * @param string $ref
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setRef(string $ref): Property
    {
        if ('' === $ref) {
            throw new InvalidArgumentException(
                sprintf(
                    'Type "%s" Property $ref cannot be empty',
                    $this->valueFHIRType->getFHIRName()
                )
            );
        }
        $this->ref = $ref;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFixed(): ?string
    {
        return $this->fixed;
    }

    /**
     * @param string|null $fixed
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setFixed(?string $fixed): Property
    {
        $this->fixed = $fixed;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * @param string|null $namespace
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setNamespace(?string $namespace): Property
    {
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
        return 'get' . ucfirst($this->getName());
    }

    /**
     * @return array
     */
    public function buildValidationMap(): array
    {
        $map = [];
        $memberOf = $this->getMemberOf();

        if (null !== ($v = $this->getPattern())) {
            $map[PHPFHIR_VALIDATION_PATTERN_NAME] = '/^' . addcslashes($v, '/\'') . '$/';
        } elseif (null !== ($v = $memberOf->getPattern())) {
            $map[PHPFHIR_VALIDATION_PATTERN_NAME] = '/^' . addcslashes($v, '/\'') . '$/';
        }

        if ($this->isCollection()) {
            if (null !== ($v = $this->getMinOccurs()) && 0 !== $v) {
                $map[PHPFHIR_VALIDATION_MIN_OCCURS_NAME] = $v;
            }
            if (null !== ($v = $this->getMaxOccurs()) && PHPFHIR_UNLIMITED !== $v) {
                $map[PHPFHIR_VALIDATION_MAX_OCCURS_NAME] = $v;
            }
        }

        if (null !== ($v = $memberOf->getMinLength()) && 0 !== $v) {
            $map[PHPFHIR_VALIDATION_MIN_LENGTH_NAME] = $v;
        }
        if (null !== ($v = $memberOf->getMaxLength()) && PHPFHIR_UNLIMITED !== $v) {
            $map[PHPFHIR_VALIDATION_MAX_LENGTH_NAME] = $v;
        }

        if ($memberOf->isEnumerated()) {
            $map[PHPFHIR_VALIDATION_ENUM_NAME] = [];
            foreach ($memberOf->getEnumeration() as $enum) {
                $map[PHPFHIR_VALIDATION_ENUM_NAME][] = $enum->getValue();
            }
        }

        return $map;
    }

    /**
     * @param bool $overloaded
     * @return \DCarbone\PHPFHIR\Definition\Property
     */
    public function setOverloaded(bool $overloaded): Property
    {
        $this->overloaded = (bool)$overloaded;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOverloaded(): bool
    {
        return $this->overloaded;
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