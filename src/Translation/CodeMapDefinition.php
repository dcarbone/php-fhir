<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Translation;

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

final class CodeMapDefinition
{
    /**
     * @param array<string, null|string> $codeMappings Source code => uniquely equivalent target code, or null if unmappable
     */
    public function __construct(
        private readonly string $sourceSystem,
        private readonly string $targetSystem,
        private readonly array $codeMappings,
    ) {
        if ('' === $sourceSystem || '' === $targetSystem) {
            throw new \InvalidArgumentException('Code map systems must not be empty.');
        }
    }

    public function getSourceSystem(): string
    {
        return $this->sourceSystem;
    }

    public function getTargetSystem(): string
    {
        return $this->targetSystem;
    }

    /** @return array<string, null|string> */
    public function getCodeMappings(): array
    {
        return $this->codeMappings;
    }

    public function merge(self $other): self
    {
        if ($this->sourceSystem !== $other->sourceSystem || $this->targetSystem !== $other->targetSystem) {
            throw new \InvalidArgumentException('Only code maps for the same source and target systems can be merged.');
        }

        $codeMappings = $this->codeMappings;
        foreach ($other->codeMappings as $sourceCode => $targetCode) {
            if (array_key_exists($sourceCode, $codeMappings) && $codeMappings[$sourceCode] !== $targetCode) {
                $codeMappings[$sourceCode] = null;
            } else {
                $codeMappings[$sourceCode] = $targetCode;
            }
        }
        ksort($codeMappings, SORT_STRING);

        return new self($this->sourceSystem, $this->targetSystem, $codeMappings);
    }
}
