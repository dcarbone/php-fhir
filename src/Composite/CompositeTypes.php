<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Composite;

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

use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\Type;

class CompositeTypes implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Composite\CompositeType[] */
    private array $_types = [];

    public function addType(Version $version, Type $type): void
    {
        $tn = $type->getFHIRName();
        if (!isset($this->_types[$tn])) {
            $this->_types[$tn] = new CompositeType(
                name: $tn,
                kind: $type->getKind(),
            );
        }
        $this->_types[$tn]->addVersionType($version, $type);
    }

    public function getTypeByFHIRName(string $fhirName): null|CompositeType
    {
        return $this->_types[$fhirName] ?? null;
    }

    public function count(): int
    {
        return count($this->_types);
    }

    /**
     * @return \DCarbone\PHPFHIR\Composite\CompositeType[]
     */
    public function getIterator(): iterable
    {
        if ([] === $this->_types) {
            return new \EmptyIterator();
        }
        return \SplFixedArray::fromArray($this->_types, preserveKeys: false);
    }
}