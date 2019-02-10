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
use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class AbstractType
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class AbstractType implements Type
{
    use DocumentationTrait;

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;

    /**
     * The raw element this type was parsed from.  Will be null for HTML and Undefined types
     *
     * @var null|\SimpleXMLElement
     */
    private $sourceSXE;

    /**
     * Name of file in definition this type was parsed from
     * @var string
     */
    private $sourceFilename;
    /**
     * The raw name of the FHIR element this type was created from
     * @var string
     */
    private $fhirName;

    /** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum */
    private $kind = null;

    /** @var string */
    private $className;

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
     * @return null|\SimpleXMLElement
     */
    public function getSourceSXE()
    {
        return $this->sourceSXE;
    }

    /**
     * @return string
     */
    public function getSourceFilename()
    {
        return $this->sourceFilename;
    }

    /**
     * @return string
     */
    public function getSourceFileBasename()
    {
        return basename($this->getSourceFilename());
    }

    /**
     * @return string
     */
    public function getFHIRName()
    {
        return $this->fhirName;
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
                'Cannot overwrite Type %s Kind from %s to %s',
                $this->getFHIRName(),
                $this->kind,
                $kind
            ));
        }
        $this->kind = $kind;
        return $this;
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
        $ns = $this->getConfig()->getNamespace();
        $fhirNS = $this->getTypeNamespace();
        if ('' !== $fhirNS) {
            $ns = "{$ns}\\{$fhirNS}";
        }
        return $leadingSlash ? '\\' . $ns : $ns;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassName($leadingSlash)
    {
        return $this->getFullyQualifiedNamespace($leadingSlash) . '\\' . $this->getClassName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFHIRName();
    }
}