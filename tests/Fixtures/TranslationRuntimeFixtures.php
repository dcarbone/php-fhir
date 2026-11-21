<?php declare(strict_types=1);

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

namespace DCarbone\PHPFHIRTests\Fixtures\Translation;

use TranslationRuntimeGenerated\Encoding\SerializeConfig;
use TranslationRuntimeGenerated\Encoding\UnserializeConfig;
use TranslationRuntimeGenerated\Encoding\XMLWriter;
use TranslationRuntimeGenerated\FHIRVersion;
use TranslationRuntimeGenerated\Types\LeafTypeInterface;
use TranslationRuntimeGenerated\Types\ResourceIDTypeInterface;
use TranslationRuntimeGenerated\Types\ResourceTypeInterface;
use TranslationRuntimeGenerated\Types\TypeInterface;
use TranslationRuntimeGenerated\Versions\VersionConfigInterface;
use TranslationRuntimeGenerated\Versions\VersionInterface;
use TranslationRuntimeGenerated\Versions\VersionTypeMapInterface;

abstract class FixtureVersion implements VersionInterface
{
    public static function getFHIRVersion(): FHIRVersion
    {
        static $versions = [];
        return $versions[static::class] ??= new FHIRVersion(
            static::NAME,
            static::SEMANTIC_VERSION,
            static::SHORT_VERSION,
            static::VERSION_INTEGER,
        );
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function getFHIRSemanticVersion(): string
    {
        return static::SEMANTIC_VERSION;
    }

    public function getFHIRShortVersion(): string
    {
        return static::SHORT_VERSION;
    }

    public function getFHIRVersionInteger(): int
    {
        return static::VERSION_INTEGER;
    }

    public function getFHIRPreRelease(): null|string
    {
        return null;
    }

    public function isFHIRPreRelease(): bool
    {
        return false;
    }

    public function getFHIRGenerationDate(): string
    {
        return '';
    }

    public function getConfig(): VersionConfigInterface
    {
        throw new \BadMethodCallException();
    }

    public function getTypeMap(): VersionTypeMapInterface
    {
        $typeMap = static::TYPE_MAP;
        return new $typeMap();
    }
}

abstract class FixtureTypeMap implements VersionTypeMapInterface
{
    public static function getMap(): array
    {
        return static::MAP;
    }

    public static function getContainableTypes(): array
    {
        return [static::MAP['Patient']];
    }

    public static function getTypeClassname(string|\stdClass|\SimpleXMLElement $input): null|string
    {
        return is_string($input) ? static::MAP[$input] ?? null : null;
    }

    public static function getContainedTypeClassname(string|\stdClass|\SimpleXMLElement $input): null|string
    {
        return self::getTypeClassname($input);
    }

    public static function isContainableType(string|\stdClass|\SimpleXMLElement|TypeInterface $input): bool
    {
        return false;
    }

    public static function mustGetContainedTypeClassnameFromXML(\SimpleXMLElement $node): string
    {
        throw new \BadMethodCallException();
    }

    public static function mustGetContainedTypeClassnameFromJSON(\stdClass $decoded): string
    {
        throw new \BadMethodCallException();
    }

    public static function jsonUnserializeResource(string $className, \stdClass $decoded, UnserializeConfig $config): TypeInterface
    {
        throw new \BadMethodCallException();
    }

    public static function xmlUnserializeResource(string $className, \SimpleXMLElement $element, UnserializeConfig $config): TypeInterface
    {
        throw new \BadMethodCallException();
    }
}

abstract class FixtureType implements TypeInterface
{
    public function _getValidationErrors(): array
    {
        return [];
    }

    public function jsonSerialize(): mixed
    {
        return null;
    }

    public function __toString(): string
    {
        return $this->_getFHIRTypeName();
    }
}

abstract class FixtureLeaf extends FixtureType implements LeafTypeInterface
{
    public function __construct(private null|string $value = null)
    {
    }

    public function _getFHIRTypeName(): string
    {
        return 'string-primitive';
    }

    public function getValue(): null|string
    {
        return $this->value;
    }

    public function setValue(null|string $value): self
    {
        $this->value = $value;
        return $this;
    }
}

abstract class FixtureName extends FixtureType
{
    #[FixtureLeaf]
    protected null|FixtureLeaf $family = null;

    /** @var \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf[] */
    #[FixtureLeaf]
    protected array $given = [];

    public function _getFHIRTypeName(): string
    {
        return 'HumanName';
    }

    public function getFamily(): null|FixtureLeaf
    {
        return $this->family;
    }

    public function setFamily(FixtureLeaf $family): self
    {
        $this->family = $family;
        return $this;
    }

    /** @return \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf[] */
    public function getGiven(): array
    {
        return $this->given;
    }

    public function setGiven(FixtureLeaf ...$given): self
    {
        $this->given = $given;
        return $this;
    }
}

abstract class FixtureCoding extends FixtureType
{
    #[FixtureLeaf]
    protected null|FixtureLeaf $system = null;

    #[FixtureLeaf]
    protected null|FixtureLeaf $code = null;

    public function _getFHIRTypeName(): string
    {
        return 'Coding';
    }

    public function getSystem(): null|FixtureLeaf
    {
        return $this->system;
    }

    public function setSystem(FixtureLeaf $system): self
    {
        $this->system = $system;
        return $this;
    }

    public function getCode(): null|FixtureLeaf
    {
        return $this->code;
    }

    public function setCode(FixtureLeaf $code): self
    {
        $this->code = $code;
        return $this;
    }
}

abstract class FixtureCodeableConcept extends FixtureType
{
    /** @var \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding[] */
    #[FixtureCoding]
    protected array $coding = [];

    public function _getFHIRTypeName(): string
    {
        return 'CodeableConcept';
    }

    /** @return \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding[] */
    public function getCoding(): array
    {
        return $this->coding;
    }

    public function setCoding(FixtureCoding ...$coding): self
    {
        $this->coding = $coding;
        return $this;
    }
}

abstract class FixturePatient extends FixtureType implements ResourceTypeInterface
{
    #[FixtureName]
    protected null|FixtureName $name = null;

    #[FixtureCodeableConcept]
    protected null|FixtureCodeableConcept $concept = null;

    public function _getFHIRTypeName(): string
    {
        return 'Patient';
    }

    public function getName(): null|FixtureName
    {
        return $this->name;
    }

    public function setName(FixtureName $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getConcept(): null|FixtureCodeableConcept
    {
        return $this->concept;
    }

    public function setConcept(FixtureCodeableConcept $concept): self
    {
        $this->concept = $concept;
        return $this;
    }

    public function _getSourceXMLNS(): null|string
    {
        return null;
    }

    public function getId(): null|ResourceIDTypeInterface
    {
        return null;
    }

    public static function xmlUnserialize(
        string|\SimpleXMLElement   $element,
        null|UnserializeConfig     $config = null,
        null|ResourceTypeInterface $type = null,
    ): ResourceTypeInterface
    {
        throw new \BadMethodCallException();
    }

    public function xmlSerialize(
        null|XMLWriter       $xw = null,
        null|SerializeConfig $config = null,
    ): XMLWriter
    {
        throw new \BadMethodCallException();
    }

    public static function jsonUnserialize(
        string|\stdClass           $decoded,
        null|UnserializeConfig     $config = null,
        null|ResourceTypeInterface $type = null,
    ): ResourceTypeInterface
    {
        throw new \BadMethodCallException();
    }
}

namespace TranslationRuntimeGenerated\Versions\R3;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureTypeMap;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureVersion;
use TranslationRuntimeGenerated\Versions\R3\Types\Coding;
use TranslationRuntimeGenerated\Versions\R3\Types\CodeableConcept;
use TranslationRuntimeGenerated\Versions\R3\Types\Leaf;
use TranslationRuntimeGenerated\Versions\R3\Types\Name;
use TranslationRuntimeGenerated\Versions\R3\Types\Patient;

final class Version extends FixtureVersion
{
    public const NAME = 'R3';
    public const SEMANTIC_VERSION = '3.0.2';
    public const SHORT_VERSION = '3.0';
    public const VERSION_INTEGER = 30000000;
    public const TYPE_MAP = VersionTypeMap::class;
}

final class VersionTypeMap extends FixtureTypeMap
{
    public const MAP = [
        'Patient' => Patient::class,
        'HumanName' => Name::class,
        'Coding' => Coding::class,
        'CodeableConcept' => CodeableConcept::class,
        'string-primitive' => Leaf::class,
    ];
}

namespace TranslationRuntimeGenerated\Versions\R3\Types;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureName;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixturePatient;
use TranslationRuntimeGenerated\FHIRVersion;
use TranslationRuntimeGenerated\Versions\R3\Version;

trait VersionedType
{
    public function _getFHIRVersion(): FHIRVersion
    {
        return Version::getFHIRVersion();
    }
}

final class Leaf extends FixtureLeaf
{
    use VersionedType;
}

final class Name extends FixtureName
{
    use VersionedType;
}

final class Patient extends FixturePatient
{
    use VersionedType;
}

final class Coding extends FixtureCoding
{
    use VersionedType;
}

final class CodeableConcept extends FixtureCodeableConcept
{
    use VersionedType;
}

namespace TranslationRuntimeGenerated\Versions\R4;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureTypeMap;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureVersion;
use TranslationRuntimeGenerated\Versions\R4\Types\Leaf;
use TranslationRuntimeGenerated\Versions\R4\Types\Name;
use TranslationRuntimeGenerated\Versions\R4\Types\Patient;
use TranslationRuntimeGenerated\Versions\R4\Types\Coding;
use TranslationRuntimeGenerated\Versions\R4\Types\CodeableConcept;

final class Version extends FixtureVersion
{
    public const NAME = 'R4';
    public const SEMANTIC_VERSION = '4.0.1';
    public const SHORT_VERSION = '4.0';
    public const VERSION_INTEGER = 40000000;
    public const TYPE_MAP = VersionTypeMap::class;
}

final class VersionTypeMap extends FixtureTypeMap
{
    public const MAP = [
        'Patient' => Patient::class,
        'HumanName' => Name::class,
        'Coding' => Coding::class,
        'CodeableConcept' => CodeableConcept::class,
        'string-primitive' => Leaf::class,
    ];
}

namespace TranslationRuntimeGenerated\Versions\R4\Types;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureName;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixturePatient;
use TranslationRuntimeGenerated\FHIRVersion;
use TranslationRuntimeGenerated\Versions\R4\Version;

trait VersionedType
{
    public function _getFHIRVersion(): FHIRVersion
    {
        return Version::getFHIRVersion();
    }
}

final class Leaf extends FixtureLeaf
{
    use VersionedType;
}

final class Name extends FixtureName
{
    use VersionedType;

    #[FixtureLeaf]
    protected null|FixtureLeaf $legacy = null;

    public function getLegacy(): null|FixtureLeaf
    {
        return $this->legacy;
    }

    public function setLegacy(FixtureLeaf $legacy): self
    {
        $this->legacy = $legacy;
        return $this;
    }
}

final class Patient extends FixturePatient
{
    use VersionedType;
}

final class Coding extends FixtureCoding
{
    use VersionedType;
}

final class CodeableConcept extends FixtureCodeableConcept
{
    use VersionedType;
}

namespace TranslationRuntimeGenerated\Versions\R6;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureTypeMap;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureVersion;
use TranslationRuntimeGenerated\Versions\R6\Types\Coding;
use TranslationRuntimeGenerated\Versions\R6\Types\CodeableConcept;
use TranslationRuntimeGenerated\Versions\R6\Types\Leaf;
use TranslationRuntimeGenerated\Versions\R6\Types\Name;
use TranslationRuntimeGenerated\Versions\R6\Types\Patient;

final class Version extends FixtureVersion
{
    public const NAME = 'R6';
    public const SEMANTIC_VERSION = '6.0.0';
    public const SHORT_VERSION = '6.0';
    public const VERSION_INTEGER = 60000000;
    public const TYPE_MAP = VersionTypeMap::class;
}

final class VersionTypeMap extends FixtureTypeMap
{
    public const MAP = [
        'Patient' => Patient::class,
        'HumanName' => Name::class,
        'Coding' => Coding::class,
        'CodeableConcept' => CodeableConcept::class,
        'string-primitive' => Leaf::class,
    ];
}

namespace TranslationRuntimeGenerated\Versions\R6\Types;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureName;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixturePatient;
use TranslationRuntimeGenerated\FHIRVersion;
use TranslationRuntimeGenerated\Versions\R6\Version;

trait VersionedType
{
    public function _getFHIRVersion(): FHIRVersion
    {
        return Version::getFHIRVersion();
    }
}

final class Leaf extends FixtureLeaf
{
    use VersionedType;
}

final class Name extends FixtureName
{
    use VersionedType;
}

final class Patient extends FixturePatient
{
    use VersionedType;
}

final class Coding extends FixtureCoding
{
    use VersionedType;
}

final class CodeableConcept extends FixtureCodeableConcept
{
    use VersionedType;
}

namespace TranslationRuntimeGenerated\Versions\R4B;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureTypeMap;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureVersion;
use TranslationRuntimeGenerated\Versions\R4B\Types\CodeableConcept;
use TranslationRuntimeGenerated\Versions\R4B\Types\Coding;
use TranslationRuntimeGenerated\Versions\R4B\Types\Leaf;
use TranslationRuntimeGenerated\Versions\R4B\Types\Name;
use TranslationRuntimeGenerated\Versions\R4B\Types\Patient;

final class Version extends FixtureVersion
{
    public const NAME = 'R4B';
    public const SEMANTIC_VERSION = '4.3.0';
    public const SHORT_VERSION = '4.3';
    public const VERSION_INTEGER = 40300000;
    public const TYPE_MAP = VersionTypeMap::class;
}

final class VersionTypeMap extends FixtureTypeMap
{
    public const MAP = [
        'Patient' => Patient::class,
        'HumanName' => Name::class,
        'Coding' => Coding::class,
        'CodeableConcept' => CodeableConcept::class,
        'string-primitive' => Leaf::class,
    ];
}

namespace TranslationRuntimeGenerated\Versions\R4B\Types;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureName;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixturePatient;
use TranslationRuntimeGenerated\FHIRVersion;
use TranslationRuntimeGenerated\Versions\R4B\Version;

trait VersionedType
{
    public function _getFHIRVersion(): FHIRVersion
    {
        return Version::getFHIRVersion();
    }
}

final class Leaf extends FixtureLeaf
{
    use VersionedType;
}

final class Name extends FixtureName
{
    use VersionedType;
}

final class Patient extends FixturePatient
{
    use VersionedType;
}

final class Coding extends FixtureCoding
{
    use VersionedType;
}

final class CodeableConcept extends FixtureCodeableConcept
{
    use VersionedType;
}

namespace TranslationRuntimeGenerated\Versions\R5;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureTypeMap;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureVersion;
use TranslationRuntimeGenerated\Versions\R5\Types\Leaf;
use TranslationRuntimeGenerated\Versions\R5\Types\Name;
use TranslationRuntimeGenerated\Versions\R5\Types\Patient;
use TranslationRuntimeGenerated\Versions\R5\Types\Coding;
use TranslationRuntimeGenerated\Versions\R5\Types\CodeableConcept;

final class Version extends FixtureVersion
{
    public const NAME = 'R5';
    public const SEMANTIC_VERSION = '5.0.0';
    public const SHORT_VERSION = '5.0';
    public const VERSION_INTEGER = 50000000;
    public const TYPE_MAP = VersionTypeMap::class;
}

final class VersionTypeMap extends FixtureTypeMap
{
    public const MAP = [
        'Patient' => Patient::class,
        'HumanName' => Name::class,
        'Coding' => Coding::class,
        'CodeableConcept' => CodeableConcept::class,
        'string-primitive' => Leaf::class,
    ];
}

namespace TranslationRuntimeGenerated\Versions\R5\Types;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureName;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixturePatient;
use TranslationRuntimeGenerated\FHIRVersion;
use TranslationRuntimeGenerated\Versions\R5\Version;

trait VersionedType
{
    public function _getFHIRVersion(): FHIRVersion
    {
        return Version::getFHIRVersion();
    }
}

final class Leaf extends FixtureLeaf
{
    use VersionedType;
}

final class Name extends FixtureName
{
    use VersionedType;
}

final class Patient extends FixturePatient
{
    use VersionedType;
}

final class Coding extends FixtureCoding
{
    use VersionedType;
}

final class CodeableConcept extends FixtureCodeableConcept
{
    use VersionedType;
}
