<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

class VersionDefaultConfig
{
    private const _UNSERIALIZE_CONFIG_KEYS = [
        'libxmlOpts',
        'libxmlOptMask',
        'jsonDecodeMaxDepth',
        'jsonDecodeOpts',
        'jsonDecodeOptMask',
    ];

    private const _SERIALIZE_CONFIG_KEYS = [
        'overrideSourceXMLNS',
        'rootXMLNS',
        'xhtmlLibxmlOpts',
        'xhtmlLibxmlOptMask',
    ];

    private const _CLIENT_CONFIG_KEYS = [
        'address',
        'defaultFormat',
        'defaultQueryParams',
        'curlOpts',
        'parseResponseHeaders',
    ];

    private const _CLIENT_CONFIG_VALID_FORMATS = ['JSON', 'XML'];

    /** @var array */
    private array $_unserializeConfig = [];
    /** @var array */
    private array $_serializeConfig = [];
    /** @var array */
    private array $_clientConfig = [];

    /**
     * @param array $unserializeConfig
     * @param array $serializeConfig
     * @param array $clientConfig
     */
    public function __construct(array $unserializeConfig = [],
                                array $serializeConfig = [],
                                array $clientConfig = [])
    {
        $this->setUnserializeConfig($unserializeConfig);
        $this->setSerializeConfig($serializeConfig);
        $this->setClientConfig($clientConfig);
    }

    public static function fromArray(array $config): self
    {
        $c = new self();
        foreach ($config as $k => $v) {
            match($k) {
                'unserializeConfig' => $c->setUnserializeConfig($v),
                'serializeConfig'   => $c->setSerializeConfig($v),
                'clientConfig'      => $c->setClientConfig($v),
                default => throw new \UnexpectedValueException(sprintf(
                    'Unknown configuration field "%s" specified',
                    $k
                )),
            };
        }
        return $c;
    }

    /**
     * @param array $config
     * @return self
     */
    public function setUnserializeConfig(array $config): self
    {
        $this->_unserializeConfig = [];
        if ([] === $config) {
            return $this;
        }
        if (array_key_exists('libxmlOpts', $config) && array_key_exists('libxmlOptMask', $config)) {
            throw new \DomainException('Cannot specify both "libxmlOpts" and "libxmlOptMask" keys.');
        }
        if (array_key_exists('jsonDecodeOpts', $config) && array_key_exists('jsonDecodeOptMask', $config)) {
            throw new \DomainException('Cannot specify both "jsonDecodeOpts" and "jsonDecodeOptMask" keys.');
        }
        foreach (self::_UNSERIALIZE_CONFIG_KEYS as $k) {
            if (!array_key_exists($k, $config)) {
                continue;
            }
            $this->_unserializeConfig[$k] = match ($k) {
                'libxmlOpts' => intval($config[$k]),
                'libxmlOptMask' => is_string($config[$k]) && preg_match('{^[A-Z0-9_\s|]+$}', $config[$k])
                    ? $config[$k]
                    : throw new \InvalidArgumentException(sprintf(
                        'Value provided to "libxmlOptMask" is either not a string or is an invalid options mask: %s',
                        $config[$k],
                    )),
                'jsonDecodeMaxDepth' => intval($config[$k]),
                'jsonDecodeOpts' => intval($config[$k]),
                'jsonDecodeOptMask'=> is_string($config[$k]) && preg_match('{^[A-Z0-9_\s|]+$}', $config[$k])
                    ? $config[$k]
                    : throw new \InvalidArgumentException(sprintf(
                        'Value provided to "jsonDecodeOptMask" is either not a string or is an invalid options mask: %s',
                        $config[$k],
                    )),

                default => throw new \UnexpectedValueException(sprintf(
                    'Unknown unserialize config key "%s"',
                    $k,
                ))
            };
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getUnserializeConfig(): array
    {
        return $this->_unserializeConfig;
    }

    /**
     * @param array $config
     * @return self
     */
    public function setSerializeConfig(array $config): self
    {
        $this->_serializeConfig = [];
        if ([] === $config) {
            return $this;
        }
        if (array_key_exists('xhtmlLibxmlOpts', $config) && array_key_exists('xhtmlLibxmlOptMask', $config)) {
            throw new \DomainException('Cannot specify both "xhtmlLibxmlOpts" and "xhtmlLibxmlOptMask" keys.');
        }
        foreach (self::_SERIALIZE_CONFIG_KEYS as $k) {
            if (!array_key_exists($k, $config)) {
                continue;
            }
            $this->_serializeConfig[$k] = match ($k) {
                'overrideSourceXMLNS' => (bool)$config[$k],
                'rootXMLNS' => null === $config[$k] ? null : (string)$config[$k],
                'xhtmlLibxmlOpts' => intval($config[$k]),
                'xhtmlLibxmlOptMask' => is_string($config[$k]) && preg_match('{^[A-Z0-9_\s|]+$}', $config[$k])
                    ? $config[$k]
                    : throw new \InvalidArgumentException(sprintf(
                        'Value provided to "xhtmlLibxmlOptMask" is either not a string or is an invalid options mask: %s',
                        $config[$k],
                    )),

                default => throw new \UnexpectedValueException(sprintf(
                    'Unknown serialize config key "%s"',
                    $k,
                ))
            };
            if ($this->_serializeConfig['overrideSourceXMLNS'] ?? false) {
                if (!isset($this->_serializeConfig['rootXMLNS']) || '' === $this->_serializeConfig['rootXMLNS']) {
                    throw new \DomainException('Must specify rootXMLNS if overrideSourceXMLNS is true');
                }
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getSerializeConfig(): array
    {
        return $this->_serializeConfig;
    }

    /**
     * @param array $config
     * @return self
     */
    public function setClientConfig(array $config): self
    {
        $this->_clientConfig = [];
        if ([] === $config) {
            return $this;
        }
        if (!array_key_exists('address', $config) || '' === trim((string)$config['address'])) {
            throw new \InvalidArgumentException(
                'Client config must include a non-empty "address" key.'
            );
        }
        foreach (self::_CLIENT_CONFIG_KEYS as $k) {
            if (!array_key_exists($k, $config)) {
                continue;
            }
            $this->_clientConfig[$k] = match ($k) {
                'address' => (string)$config[$k],
                'defaultFormat' => in_array($config[$k], self::_CLIENT_CONFIG_VALID_FORMATS, true)
                    ? $config[$k]
                    : throw new \InvalidArgumentException(sprintf(
                        'Value "%s" is not a valid defaultFormat; must be one of: %s',
                        $config[$k],
                        implode(', ', self::_CLIENT_CONFIG_VALID_FORMATS),
                    )),
                'defaultQueryParams' => is_array($config[$k])
                    ? $config[$k]
                    : throw new \InvalidArgumentException('"defaultQueryParams" must be an array.'),
                'curlOpts' => is_array($config[$k])
                    ? $config[$k]
                    : throw new \InvalidArgumentException('"curlOpts" must be an array.'),
                'parseResponseHeaders' => (bool)$config[$k],
                default => throw new \UnexpectedValueException(sprintf(
                    'Unknown client config key "%s"',
                    $k,
                )),
            };
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getClientConfig(): array
    {
        return $this->_clientConfig;
    }

    public function toArray(): array
    {
        $out = [];
        if ([] !== $this->_unserializeConfig) {
            $out['unserializeConfig'] = $this->_unserializeConfig;
        }
        if ([] !== $this->_serializeConfig) {
            $out['serializeConfig'] = $this->_serializeConfig;
        }
        if ([] !== $this->_clientConfig) {
            $out['clientConfig'] = $this->_clientConfig;
        }
        return $out;
    }
}