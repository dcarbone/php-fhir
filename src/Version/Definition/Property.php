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

use DCarbone\PHPFHIR\Enum\PropertyUse;
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

    /** @var \DCarbone\PHPFHIR\Version\Definition\Type */
    private Type $memberOf;

    /** @var string|null */
    private null|string $name = null;

    /** @var string|null */
    private null|string $valueFHIRTypeName = null;

    /** @var null|string */
    private null|string $rawPHPValue = null;

    /** @var int */
    private int $minOccurs = 0;
    /** @var null|int */
    private null|int $maxOccurs = null;

    /** @var null|string */
    private null|string $pattern = null;

    /** @var null|\DCarbone\PHPFHIR\Version\Definition\Type */
    private ?Type $valueFHIRType = null;

    /** @var \DCarbone\PHPFHIR\Enum\PropertyUse */
    private PropertyUse $use;

    /** @var string|null */
    private null|string $ref = null;

    /** @var null|string */ // TODO: what the hell is this...?
    private null|string $fixed = null;

    /** @var null|string */ // NOTE: not a php namespace
    private null|string $namespace = null;

    /** @var bool */
    private bool $overloaded = false;

    /**
     * Property constructor.
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $memberOf
     * @param \SimpleXMLElement $sxe
     * @param string $sourceFilename
     */
    public function __construct(Type $memberOf, SimpleXMLElement $sxe, string $sourceFilename)
    {
        $this->memberOf = $memberOf;
        $this->sourceSXE = $sxe;
        $this->sourceFilename = $sourceFilename;
        $this->use = PropertyUse::OPTIONAL;
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
        return $this->memberOf;
    }

    /**
     * @return string|null
     */
    public function getName(): null|string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
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
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
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
     * @return \DCarbone\PHPFHIR\Enum\PropertyUse
     */
    public function getUse(): PropertyUse
    {
        return $this->use;
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\PropertyUse $use
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setUse(PropertyUse $use): Property
    {
        $this->use = $use;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRef(): null|string
    {
        return $this->ref;
    }

    /**
     * @param string $ref
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
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
     * @return array
     */
    public function buildValidationMap(): array
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
            foreach ($memberOf->getEnumeration()->getIterator() as $enum) {
                $map[PHPFHIR_VALIDATION_ENUM_NAME][] = $enum->getValue();
            }
        }



        return $map;
    }

    /**
     * @param bool $overloaded
     * @return \DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function setOverloaded(bool $overloaded): Property
    {
        $this->overloaded = $overloaded;
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