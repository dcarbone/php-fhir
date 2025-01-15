<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version;

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

class DefaultConfig
{
    private const _UNSERIALIZE_CONFIG_KEYS = [
        'libxmlOpts',
        'libxmlOptMask',
        'jsonDecodeMaxDepth',
    ];

    private const _SERIALIZE_CONFIG_KEYS = [
        'overrideSourceXMLNS',
        'rootXMLNS',
        'xhtmlLibxmlOpts',
        'xhtmlLibxmlOptMask',
    ];

    /** @var array */
    private array $_unserializeConfig = [];
    /** @var array */
    private array $_serializeConfig = [];

    /**
     * @param array $unserializeConfig
     * @param array $serializeConfig
     */
    public function __construct(array $unserializeConfig = [],
                                array $serializeConfig = [])
    {
        $this->setUnserializeConfig($unserializeConfig);
        $this->setSerializeConfig($serializeConfig);
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
        foreach (self::_UNSERIALIZE_CONFIG_KEYS as $k) {
            if (!array_key_exists($k, $config)) {
                continue;
            }
            $this->_unserializeConfig[$k] = match ($k) {
                'libxmlOpts' => intval($config[$k]),
                'libxmlOptMask' => is_string($config[$k]) && preg_match('{^[A-Z0-9_\s|]+}$}', $config[$k])
                    ? $config[$k]
                    : throw new \InvalidArgumentException(sprintf(
                        'Value provided to "libxmlOptMask" is either not a string or is an invalid options mask: %s',
                        $config[$k],
                    )),
                'jsonDecodeMaxDepth' => intval($config[$k]),

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
                'xhtmlLibxmlOptMask' => is_string($config[$k]) && preg_match('{^[A-Z0-9_\s|]+}$}', $config[$k])
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

    public function toArray(): array
    {
        $out = [];
        if ([] !== $this->_unserializeConfig) {
            $out['unserializeConfig'] = $this->_unserializeConfig;
        }
        if ([] !== $this->_serializeConfig) {
            $out['serializeConfig'] = $this->_serializeConfig;
        }
        return $out;
    }
}