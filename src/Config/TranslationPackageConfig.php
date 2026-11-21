<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Config;

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

use DCarbone\PHPFHIR\Utilities\FileUtils;

final class TranslationPackageConfig
{
    private const _CONFIG_KEYS = ['sourceVersion', 'targetVersion', 'source'];

    public function __construct(
        private readonly string $sourceVersion,
        private readonly string $targetVersion,
        private readonly string $source,
    ) {
        if ('' === trim($sourceVersion) || '' === trim($targetVersion)) {
            throw new \InvalidArgumentException('Translation package sourceVersion and targetVersion must not be empty.');
        }
        if ($sourceVersion === $targetVersion) {
            throw new \InvalidArgumentException('Translation package sourceVersion and targetVersion must differ.');
        }
        FileUtils::assertPathReadable($source, 'Translation package source');
    }

    public static function fromArray(array $config): self
    {
        foreach (array_keys($config) as $key) {
            if (!in_array($key, self::_CONFIG_KEYS, true)) {
                throw new \UnexpectedValueException(sprintf(
                    'Unknown translation package config key "%s". Allowed: %s',
                    $key,
                    implode(', ', self::_CONFIG_KEYS),
                ));
            }
        }
        foreach (self::_CONFIG_KEYS as $key) {
            if (!isset($config[$key]) || !is_string($config[$key])) {
                throw new \InvalidArgumentException(sprintf('Translation package "%s" must be a string.', $key));
            }
        }

        return new self(
            sourceVersion: $config['sourceVersion'],
            targetVersion: $config['targetVersion'],
            source: $config['source'],
        );
    }

    public function getSourceVersion(): string
    {
        return $this->sourceVersion;
    }

    public function getTargetVersion(): string
    {
        return $this->targetVersion;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
