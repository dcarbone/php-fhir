<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Version\Definition\Properties;
use DCarbone\PHPFHIR\Version\Definition\Type;

class CompositeType
{
    private string $_name;
    private Properties $_properties;
    /** @var \DCarbone\PHPFHIR\Version\Definition\Type[] */
    private array $_verionTypes = [];
    private bool $_compiled = false;

    public function __construct(string $name)
    {
        $this->_name = $name;
        $this->_properties = new Properties();
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getProperties(): Properties
    {
        return $this->_properties;
    }

    public function isCompiled(): bool
    {
        return $this->_compiled;
    }

    public function addVersionType(Type $type): void
    {
        if ($this->_compiled) {
            throw new \LogicException(sprintf(
                'Composite type "%s" is already compiled, cannot add Version "%s" type "%s"',
                $this->_name,
                $type->getVersion()->getName(),
                $type->getFHIRName(),
            ));
        }
        if ($type->getKind() !== $this->_typeKind) {
            throw new \OutOfBoundsException(sprintf(
                'Composite type "%s" is of kind "%s", cannot add Version "%s" type "%s" of kind "%s"',
                $this->_name,
                $this->_typeKind->name,
                $type->getVersion()->getName(),
                $type->getFHIRName(),
                $type->getKind()->name,
            ));
        }

        $this->_verionTypes[] = $type;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Type[]
     */
    public function getVersionTypesInterator(): iterable
    {
        if ([] === $this->_verionTypes) {
            return new \EmptyIterator();
        }
        return new \ArrayIterator($this->_verionTypes);
    }

    public function compile(): void
    {
        if ($this->_compiled) {
            throw new \LogicException(sprintf(
                'Cannot copmile composite type "%s" more than once',
                $this->_name,
            ));
        }

        $this->_compiled = true;

        $fulLMap = [];

        foreach ($this->_verionTypes as $vt) {
            $vn = $vt->getVersion()->getName();
            $fulLMap[$vn] = [];
            foreach ($vt->getAllPropertiesIndexedIterator() as $typeProp) {
                $fulLMap[$vn][] = $typeProp->getName();
            }
        }

        $commonNames = [];

        foreach ($fulLMap as $propMap) {
            $commonNames = array_intersect(...$fulLMap);
        }

        var_dump($commonNames);
    }
}
