<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version;

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

/**
 * Class VersoinConfig
 * @package DCarbone\PHPFHIR\Version
 */
class VersionDefaultConfig
{
    private const _UNSERIALIZE_CONFIG_KEYS = [
        'libxmlOpts',
        'jsonDecodeMaxDepth',
    ];

    private const _SERIALIZE_CONFIG_KEYS = [
        'overrideSourceXmlns',
        'rootXmlns',
    ];

    /** @var array */
    private array $_unserializeConfig = [];
    /** @var array */
    private array $_serializeConfig = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach (VersionDefaultConfigKeyEnum::cases() as $k) {
            if (isset($config[$k->value]) || array_key_exists($k->value, $config)) {
                $this->{"set{$k->value}"}($config[$k->value]);
            }
        }
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
        foreach (self::_UNSERIALIZE_CONFIG_KEYS as $k) {
            if (isset($config[$k]) || array_key_exists($k, $config)) {
                $this->_unserializeConfig[$k] = $config[$k];
            }
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
        foreach (self::_SERIALIZE_CONFIG_KEYS as $k) {
            if (isset($config[$k]) || array_key_exists($k, $config)) {
                $this->_serializeConfig[$k] = $config[$k];
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
        return [
            'serializeConfig' => $this->getSerializeConfig(),
            'unserializeConfig' => $this->getUnserializeConfig(),
        ];
    }
}