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
use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class Types
 * @package DCarbone\PHPFHIR
 */
class Types implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Definition\Type[] */
    private $types = [];

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;

    /**
     * FHIRTypes constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     */
    public function __construct(VersionConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return ['types' => $this->types];
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByName($name)
    {
        foreach ($this->types as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByClassName($name)
    {
        foreach ($this->types as $type) {
            if ($type->getClassName() === $name) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $fqn
     * @param bool $leadingSlash
     * @return \DCarbone\PHPFHIR\Definition\Type|null
     */
    public function getTypeByFQN($fqn, $leadingSlash)
    {
        foreach ($this->types as $type) {
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
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return \DCarbone\PHPFHIR\Definition\Types
     */
    public function addType(Type &$type)
    {
        $tname = $type->getName();
        foreach ($this->types as $current) {
            if ($type === $current) {
                return $this;
            }
            if ($current->getName() === $tname && $current->getType() === $type->getType()) {
                // this happens with FHIR types sometimes...
                if ($current instanceof FHIRType && $type instanceof FHIRType) {
                    $this->config->getLogger()->notice(sprintf(
                        'Type "%s" was previously defined in file "%s", found again in "%s".  Keeping original',
                        $tname,
                        $current->getSourceFileBasename(),
                        $type->getSourceFileBasename()
                    ));
                    $type = $current;
                    return $this;
                }
                // if i'm trying to redefine a php type, yell at myself
                throw new \RuntimeException(sprintf(
                    'Why are you redefining PHP type "%s"?',
                    $type->getName()
                ));
            }

            // if there is a type mismatch, we need to change what we must defer to the FHIR type
            throw new \DomainException(sprintf(
                'Type "%s" is already defined and as type "%s", cannot replace with type of "%s"',
                $type->getName(),
                $current->getType(),
                $type->getType()
            ));
        }
        $this->types[] = $type;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->types);
    }

    /**
     * Returns iterator of types natcase sorted by FHIR type name
     *
     * @return \DCarbone\PHPFHIR\Definition\Type[]
     */
    public function getSortedIterator()
    {
        $tmp = $this->types;
        usort(
            $tmp,
            function (Type $t1, Type $t2) {
                return strnatcasecmp($t1->getName(), $t2->getName());
            }
        );
        return new \ArrayIterator($tmp);
    }

//    /**
//     * @param string $fhirName
//     * @param \SimpleXMLElement $sourceSXE
//     * @param string $sourceFileName
//     * @return \DCarbone\PHPFHIR\Definition\Type
//     */
//    public function newType($fhirName, \SimpleXMLElement $sourceSXE, $sourceFileName)
//    {
//        $type = new Type($this->config, $fhirName, $sourceSXE, $sourceFileName);
//        $this->addType($type);
//        return $type;
//    }
//
//    /**
//     * @param string $fhirName
//     * @return \DCarbone\PHPFHIR\Definition\Type
//     */
//    public function newHTMLValueType($fhirName)
//    {
//        $type = new Type($this->config, $fhirName);
//        $type->setKind(new TypeKindEnum(TypeKindEnum::HTML_VALUE));
//        $this->addType($type);
//        return $type;
//    }
//
//
//    /**
//     * @param string $fhirName
//     * @return \DCarbone\PHPFHIR\Definition\Type
//     */
//    public function newUndefinedType($fhirName)
//    {
//        $type = new Type($this->config, $fhirName);
//        $type->setKind(new TypeKindEnum(TypeKindEnum::UNDEFINED));
//        $this->addType($type);
//        return $type;
//    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->types);
    }
}