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
use TranslationRuntimeGenerated\Encoding\ValueXmlLocationEnum;
use TranslationRuntimeGenerated\Encoding\XMLWriter;
use TranslationRuntimeGenerated\FHIRVersion;
use TranslationRuntimeGenerated\Types\LeafTypeInterface;
use TranslationRuntimeGenerated\Types\PrimitiveContainerTypeInterface;
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

final class FixturePrimitive extends FixtureLeaf
{
    public function _getFHIRVersion(): FHIRVersion
    {
        return \TranslationRuntimeGenerated\Versions\R4\Version::getFHIRVersion();
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

    #[FixtureLeaf]
    protected null|FixtureLeaf $version = null;

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

    public function getVersion(): null|FixtureLeaf
    {
        return $this->version;
    }

    public function setVersion(FixtureLeaf $version): self
    {
        $this->version = $version;
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

enum FixtureStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

/** An enum-bound element, like a generated FHIRAccountStatus. */
abstract class FixtureStatus extends FixtureType implements PrimitiveContainerTypeInterface
{
    public function __construct(private null|FixtureStatusEnum $value = null)
    {
    }

    public function _getFHIRTypeName(): string
    {
        return 'AccountStatus';
    }

    public function getValue(): null|FixtureLeaf
    {
        return null === $this->value ? null : new FixturePrimitive($this->value->value);
    }

    public function _getValueAsString(): string
    {
        return $this->value?->value ?? '';
    }

    public function _nonValueFieldDefined(): bool
    {
        return false;
    }

    public function xmlSerialize(XMLWriter $xw, SerializeConfig $config, null|ValueXmlLocationEnum $valueLocation = null): void
    {
        throw new \BadMethodCallException();
    }
}

abstract class FixtureExtension extends FixtureType
{
    #[FixtureLeaf]
    protected null|FixtureLeaf $url = null;

    #[FixtureLeaf]
    protected null|FixtureLeaf $valueString = null;

    #[FixtureLeaf]
    protected null|FixtureLeaf $valueCode = null;

    #[FixtureCodeableConcept]
    protected null|FixtureCodeableConcept $valueCodeableConcept = null;

    #[FixtureCoding]
    protected null|FixtureCoding $valueCoding = null;

    /** @var \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtension[] */
    #[FixtureExtension]
    protected array $extension = [];

    abstract protected function newLeaf(string $value): FixtureLeaf;

    public function _getFHIRTypeName(): string
    {
        return 'Extension';
    }

    public function getUrl(): null|FixtureLeaf
    {
        return $this->url;
    }

    public function setUrl(string|FixtureLeaf $url): self
    {
        $this->url = is_string($url) ? $this->newLeaf($url) : $url;
        return $this;
    }

    public function getValueString(): null|FixtureLeaf
    {
        return $this->valueString;
    }

    public function setValueString(string|FixtureLeaf $valueString): self
    {
        $this->valueString = is_string($valueString) ? $this->newLeaf($valueString) : $valueString;
        return $this;
    }

    public function getValueCode(): null|FixtureLeaf
    {
        return $this->valueCode;
    }

    public function setValueCode(string|FixtureLeaf $valueCode): self
    {
        $this->valueCode = is_string($valueCode) ? $this->newLeaf($valueCode) : $valueCode;
        return $this;
    }

    public function getValueCodeableConcept(): null|FixtureCodeableConcept
    {
        return $this->valueCodeableConcept;
    }

    public function setValueCodeableConcept(FixtureCodeableConcept $valueCodeableConcept): self
    {
        $this->valueCodeableConcept = $valueCodeableConcept;
        return $this;
    }

    public function getValueCoding(): null|FixtureCoding
    {
        return $this->valueCoding;
    }

    public function setValueCoding(FixtureCoding $valueCoding): self
    {
        $this->valueCoding = $valueCoding;
        return $this;
    }

    /** @return \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtension[] */
    public function getExtension(): array
    {
        return $this->extension;
    }

    public function setExtension(FixtureExtension ...$extension): self
    {
        $this->extension = $extension;
        return $this;
    }
}

/** Gives a resource the extension fields that a generated resource inherits from its base type. */
trait FixtureExtensionHolder
{
    /** @var \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtension[] */
    #[FixtureExtension]
    protected array $extension = [];

    /** @var \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtension[] */
    #[FixtureExtension]
    protected array $modifierExtension = [];

    /** @return \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtension[] */
    public function getExtension(): array
    {
        return $this->extension;
    }

    public function setExtension(FixtureExtension ...$extension): self
    {
        $this->extension = $extension;
        return $this;
    }

    /** @return \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtension[] */
    public function getModifierExtension(): array
    {
        return $this->modifierExtension;
    }

    public function setModifierExtension(FixtureExtension ...$modifierExtension): self
    {
        $this->modifierExtension = $modifierExtension;
        return $this;
    }
}

abstract class FixtureBalanceDetail extends FixtureType
{
    #[FixtureCodeableConcept]
    protected null|FixtureCodeableConcept $note = null;

    public function _getFHIRTypeName(): string
    {
        return 'Patient.balance.detail';
    }

    public function getNote(): null|FixtureCodeableConcept
    {
        return $this->note;
    }

    public function setNote(FixtureCodeableConcept $note): self
    {
        $this->note = $note;
        return $this;
    }
}

abstract class FixtureBalance extends FixtureType
{
    #[FixtureCodeableConcept]
    protected null|FixtureCodeableConcept $term = null;

    /** @var \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept[] */
    #[FixtureCodeableConcept]
    protected array $tags = [];

    #[FixtureBalanceDetail]
    protected null|FixtureBalanceDetail $detail = null;

    /** Deliberately has no slice in the test definitions, so it cannot be carried. */
    #[FixtureCodeableConcept]
    protected null|FixtureCodeableConcept $uncarried = null;

    public function _getFHIRTypeName(): string
    {
        return 'Patient.balance';
    }

    public function getTerm(): null|FixtureCodeableConcept
    {
        return $this->term;
    }

    public function setTerm(FixtureCodeableConcept $term): self
    {
        $this->term = $term;
        return $this;
    }

    /** @return \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept[] */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(FixtureCodeableConcept ...$tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getDetail(): null|FixtureBalanceDetail
    {
        return $this->detail;
    }

    public function setDetail(FixtureBalanceDetail $detail): self
    {
        $this->detail = $detail;
        return $this;
    }

    public function getUncarried(): null|FixtureCodeableConcept
    {
        return $this->uncarried;
    }

    public function setUncarried(FixtureCodeableConcept $uncarried): self
    {
        $this->uncarried = $uncarried;
        return $this;
    }
}

/** Fields that exist only in the source version of the extension-emission tests. */
trait FixtureSourceOnlyFields
{
    #[FixtureStatus]
    protected null|FixtureStatus $status = null;

    #[FixtureBalance]
    protected null|FixtureBalance $balance = null;

    #[FixtureCodeableConcept]
    protected null|FixtureCodeableConcept $currency = null;

    /** @var \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept[] */
    #[FixtureCodeableConcept]
    protected array $categories = [];

    #[FixtureCoding]
    protected null|FixtureCoding $flag = null;

    #[FixtureLeaf]
    protected null|FixtureLeaf $undescribed = null;

    public function getStatus(): null|FixtureStatus
    {
        return $this->status;
    }

    public function setStatus(FixtureStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getBalance(): null|FixtureBalance
    {
        return $this->balance;
    }

    public function setBalance(FixtureBalance $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    public function getCurrency(): null|FixtureCodeableConcept
    {
        return $this->currency;
    }

    public function setCurrency(FixtureCodeableConcept $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /** @return \DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept[] */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(FixtureCodeableConcept ...$categories): self
    {
        $this->categories = $categories;
        return $this;
    }

    public function getFlag(): null|FixtureCoding
    {
        return $this->flag;
    }

    public function setFlag(FixtureCoding $flag): self
    {
        $this->flag = $flag;
        return $this;
    }

    public function getUndescribed(): null|FixtureLeaf
    {
        return $this->undescribed;
    }

    public function setUndescribed(FixtureLeaf $undescribed): self
    {
        $this->undescribed = $undescribed;
        return $this;
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

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureBalance;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureBalanceDetail;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureLeaf;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureName;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixturePatient;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureSourceOnlyFields;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureStatus;
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
    use FixtureSourceOnlyFields;
}

final class Status extends FixtureStatus
{
    use VersionedType;
}

final class Balance extends FixtureBalance
{
    use VersionedType;
}

final class BalanceDetail extends FixtureBalanceDetail
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
use TranslationRuntimeGenerated\Versions\R5\Types\Extension;

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
        'Extension' => Extension::class,
        'string' => Leaf::class,
        'string-primitive' => Leaf::class,
    ];
}

namespace TranslationRuntimeGenerated\Versions\R5\Types;

use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCodeableConcept;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureCoding;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtension;
use DCarbone\PHPFHIRTests\Fixtures\Translation\FixtureExtensionHolder;
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
    use FixtureExtensionHolder;
}

final class Extension extends FixtureExtension
{
    use VersionedType;

    protected function newLeaf(string $value): FixtureLeaf
    {
        return new Leaf($value);
    }
}

final class Coding extends FixtureCoding
{
    use VersionedType;
}

final class CodeableConcept extends FixtureCodeableConcept
{
    use VersionedType;
}
