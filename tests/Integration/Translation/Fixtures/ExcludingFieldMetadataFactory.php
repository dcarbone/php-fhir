<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Integration\Translation\Fixtures;

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

use DCarbone\PHPFHIRGenerated\Translation\FieldMetadataFactory;
use DCarbone\PHPFHIRGenerated\Translation\FieldMetadataFactoryInterface;
use DCarbone\PHPFHIRGenerated\Types\TypeInterface;

/**
 * Example user metadata factory: decorates the default factory and hides the named fields from every type, so
 * the default translators never copy them.
 */
final class ExcludingFieldMetadataFactory implements FieldMetadataFactoryInterface
{
    /** @var array<int, string> */
    private readonly array $excludedFields;

    public function __construct(
        private readonly FieldMetadataFactoryInterface $inner = new FieldMetadataFactory(),
        string ...$excludedFields,
    ) {
        $this->excludedFields = $excludedFields;
    }

    public function forType(TypeInterface $type): array
    {
        return array_diff_key($this->inner->forType($type), array_flip($this->excludedFields));
    }
}
