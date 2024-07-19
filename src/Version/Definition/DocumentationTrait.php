<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version\Definition;

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Trait DocumentationTrait
 * @package DCarbone\PHPFHIR
 */
trait DocumentationTrait
{
    /** @var array */
    private array $documentation = [];

    /**
     * @param null|string|array $documentation
     * @return \DCarbone\PHPFHIR\Version\Definition\Type|\DCarbone\PHPFHIR\Version\Definition\Property
     */
    public function addDocumentationFragment(null|string|array $documentation): Type|Property
    {
        if (null === $documentation) {
            return $this;
        }

        $phrase = implode(' ', array_filter(array_map('trim', preg_split("/[\r\n]+/", $documentation))));
        if ('' === $phrase) {
            return $this;
        }

        $words = array_filter(explode(' ', $phrase));
        $bit = '';
        $bitLen = 0;
        foreach ($words as $word) {
            $wordLen = strlen($word);
            if (($bitLen + $wordLen + 1) > PHPFHIR_DOCBLOC_MAX_LENGTH) {
                if (!in_array($bit, $this->documentation, true)) {
                    $this->documentation[] = $bit;
                }
                $bit = '';
            }
            if ('' === $bit) {
                $bit = $word;
            } else {
                $bit = "{$bit} {$word}";
            }
            $bitLen = strlen($bit);
        }
        if (!in_array($bit, $this->documentation, true)) {
            $this->documentation[] = $bit;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getDocumentation(): array
    {
        return $this->documentation;
    }

    /**
     * @return string
     */
    public function getDocumentationString(): string
    {
        return implode("\n", $this->getDocumentation());
    }

    /**
     * @param int $spaces
     * @param bool $trailingNewline
     * @return string
     */
    public function getDocBlockDocumentationFragment(int $spaces, bool $trailingNewline): string
    {
        if (!isset($this->documentation) || [] === $this->documentation) {
            return '';
        }
        $pieces = [];
        $spaces = str_repeat(' ', $spaces);
        foreach ($this->documentation as $doc) {
            $pieces[] = str_replace('@', '\\@', "{$spaces}* {$doc}");
        }
        return implode("\n", $pieces) . ($trailingNewline ? "\n" : '');
    }
}