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

/**
 * An HL7 cross-version extension that carries one source-version field in the target version.
 *
 * Choice elements are resolved to one definition per concrete source field (e.g. "valueString"), so the runtime never
 * has to guess which source field an extension belongs to.
 */
final class XverExtensionDefinition
{
    /**
     * @param string $url The extension URL. For a child this is the nested extension's url, i.e. its slice name.
     * @param string $field The concrete source field name that supplies the extension's value.
     * @param bool $modifier True if the extension must be emitted as a modifier extension.
     * @param list<string> $valueTypes The FHIR type names the extension's value may take in the target version; empty
     *        for a complex extension.
     * @param list<\DCarbone\PHPFHIR\Translation\XverExtensionDefinition> $children The nested extensions of a complex
     *        extension; empty for a simple extension that carries a single value.
     * @param null|string $datatype The name of a source datatype that does not exist in the target version, e.g.
     *        "CodeableReference".  It is emitted as a "_datatype" child so the datatype can be recognised again.
     */
    public function __construct(
        public readonly string $url,
        public readonly string $field,
        public readonly bool $modifier = false,
        public readonly array $valueTypes = [],
        public readonly array $children = [],
        public readonly null|string $datatype = null,
    ) {
        if ('' === $url || '' === $field) {
            throw new \InvalidArgumentException('An xver extension definition requires a url and a source field.');
        }
    }
}
