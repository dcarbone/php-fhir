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

use ArrayIterator;
use Countable;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/**
 * Class Types
 * @package DCarbone\PHPFHIR
 */
class Types implements Countable
{
    /** @var \DCarbone\PHPFHIR\Version\Definition\Type[] */
    private array $_types = [];

    /** @var \DCarbone\PHPFHIR\Version */
    private Version $_version;

    /**
     * This is the type that is used as a proxy type for a multitude of other types!
     * @var \DCarbone\PHPFHIR\Version\Definition\Type
     */
    private Type $_containerType;

    /**
     * This will eventually be the "Bundle" type seen
     * @var \DCarbone\PHPFHIR\Version\Definition\Type
     */
    private Type $_bundleType;

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     */
    public function __construct(Version $version)
    {
        $this->_version = $version;

        // construct "XHTML" type
        // TODO(dcarbone): this sucks.
        $xt = new Type($version, PHPFHIR_XHTML_TYPE_NAME);
        $xt->setKind(TypeKindEnum::PHPFHIR_XHTML);
        $this->addOrReturnType($xt);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return ['types' => $this->_types];
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getTypeByName(string $name): null|Type
    {
        foreach ($this->_types as $type) {
            if ($type->getFHIRName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getTypeByClassName(string $name): null|Type
    {
        foreach ($this->_types as $type) {
            if ($type->getClassName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $fqn
     * @param bool $leadingSlash
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getTypeByFQN(string $fqn, bool $leadingSlash): null|Type
    {
        foreach ($this->_types as $type) {
            if ($type->getFullyQualifiedClassName($leadingSlash) === $fqn) {
                return $type;
            }
        }
        return null;
    }

    /**
     * This method has a reference receiver as we do not want to have two separate instances of the
     * same type running around...
     *
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Version\Definition\Type
     */
    public function addOrReturnType(Type $type): Type
    {
        $tname = $type->getFHIRName();
        foreach ($this->_types as $current) {
            if ($type === $current) {
                return $current;
            }
            if ($current->getFHIRName() === $tname) {
                // this happens with FHIR types sometimes...
                $this->_version->getConfig()->getLogger()->notice(
                    sprintf(
                        'Type "%s" was previously defined in file "%s", found again in "%s".  Keeping original',
                        $tname,
                        $current->getSourceFileBasename(),
                        $type->getSourceFileBasename()
                    )
                );
                return $current;
            }
        }
        $this->_types[] = $type;
        return $type;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type[]
     */
    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->_types);
    }

    /**
     * Returns iterator of types natcase sorted by FHIR type name
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Type[]
     */
    public function getNameSortedIterator(): iterable
    {
        $tmp = $this->_types;
        usort(
            $tmp,
            function (Type $t1, Type $t2) {
                return strnatcasecmp($t1->getFHIRName(), $t2->getFHIRName());
            }
        );
        return new ArrayIterator($tmp);
    }

    /**
     * Returns iterator of types natcase sorted by FHIR type name
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Type[]
     */
    public function getNamespaceSortedIterator(): iterable
    {
        $tmp = $this->_types;
        usort(
            $tmp,
            function (Type $t1, Type $t2) {
                return strnatcasecmp($t1->getFullyQualifiedClassName(false), $t2->getFullyQualifiedClassName(false));
            }
        );
        return new ArrayIterator($tmp);
    }

    /**
     * Returns the "container" type for this version.
     *
     * Should be either "Resource.Inline" or "ResourceContainer" types.
     *
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getContainerType(): null|Type
    {
        if (!isset($this->_containerType)) {
            foreach ($this->_types as $type) {
                if ($type->getKind()->isResourceContainer($this->_version)) {
                    $this->_containerType = $type;
                    break;
                }
            }
        }
        return $this->_containerType ?? null;
    }

    /**
     * @param \DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return bool
     */
    public function isContainedType(Type $type): bool
    {
        // only bother with actual Resource types.
        if ($type->getKind()->isResourceContainer($type->getVersion())) {
            return false;
        }
        $container = $this->getContainerType();
        if (null === $container) {
            return false;
        }
        foreach ($container->getProperties()->getIterator() as $property) {
            if (($ptype = $property->getValueFHIRType()) && $ptype->getFHIRName() === $type->getFHIRName()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|null
     */
    public function getBundleType(): null|Type
    {
        if (!isset($this->_bundleType)) {
            foreach ($this->_types as $type) {
                if ($type->getFHIRName() === 'Bundle') {
                    $this->_bundleType = $type;
                    break;
                }
            }
        }
        return $this->_bundleType ?? null;
    }

    /**
     * @param string|\DCarbone\PHPFHIR\Version\Definition\Type $type
     * @return \Generator<\DCarbone\PHPFHIR\Version\Definition\Type>
     */
    public function getChildrenOfGenerator(string|Type $type): \Generator
    {
        if (is_string($type)) {
            $type = $this->getTypeByName($type);
            if (null === $type) {
                return;
            }
        }
        foreach ($this->_types as $t) {
            if (in_array($type, $t->getParentTypes(), true)) {
                yield $t;
            }
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->_types);
    }
}