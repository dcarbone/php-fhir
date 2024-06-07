<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Config;

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition;
use DCarbone\PHPFHIR\Enum\TestType;
use DCarbone\PHPFHIR\Utilities\NameUtils;
use InvalidArgumentException;

/**
 * Class Version
 * @package DCarbone\PHPFHIR\Config
 */
class Version
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $config;

    /** @var string */
    private string $name;
    /** @var string */
    private string $sourceUrl;
    /** @var string */
    private string $namespace;
    /** @var string */
    private string $testEndpoint;

    /** @var \DCarbone\PHPFHIR\Definition */
    private Definition $definition;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param string $name
     * @param array $params
     */
    public function __construct(Config $config, string $name, array $params = [])
    {
        $this->config = $config;
        $this->name = $name;

        if ('' === trim($this->name)) {
            throw new \DomainException('Version name cannot be empty.');
        }

        // attempt to set each expectd key
        foreach(VersionKeys::cases() as $key) {
            if (isset($params[$key->value])) {
                $this->{$key->value} = $params[$key->value];
            }
        }

        // default namespace to name, if no specific namespace provided
        if (!isset($this->namespace)) {
            $this->namespace = $this->name;
        }

        // require a few fields
        foreach (self::_requiredParamKeys() as $key) {
            if (!isset($this->{$key->name})) {
                throw new \DomainException(sprintf('Config must have "%s" key', $key->name));
            }
        }

        // ensure namespace is valid
        if (!NameUtils::isValidNSName($this->namespace)) {
            throw new InvalidArgumentException(
                sprintf(
                    '"%s" is not a valid PHP namespace.',
                    $this->namespace
                )
            );
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getNamespace(bool $leadingSlash): string
    {
        return $leadingSlash ? "\\{$this->namespace}" : $this->namespace;
    }

    /**
     * @return string|null
     */
    public function getTestEndpoint(): string|null
    {
        return $this->testEndpoint ?? null;
    }

    /**
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedName(bool $leadingSlash, string... $bits): string
    {
        $ns = $this->getNamespace($leadingSlash);
        $bits = array_filter($bits);
        if ([] === $bits) {
            return $ns;
        }
        return sprintf('%s\\%s', $ns, implode('\\' , $bits));
    }

    /**
     * @param \DCarbone\PHPFHIR\Enum\TestType $testType
     * @param bool $leadingSlash
     * @param string ...$bits
     * @return string
     */
    public function getFullyQualifiedTestsName(TestType $testType, bool $leadingSlash, string... $bits): string
    {
        return $this->getFullyQualifiedName($leadingSlash, $testType->namespaceSlug(), ...$bits);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition
     */
    public function getDefinition(): Definition
    {
        if (!isset($this->definition)) {
            $this->definition = new Definition($this);
        }
        return $this->definition;
    }

    /**
     * @return array
     */
    private static function _requiredParamKeys(): array
    {
        return [VersionKeys::SOURCE_URL->value, VersionKeys::NAMESPACE->value];
    }
}