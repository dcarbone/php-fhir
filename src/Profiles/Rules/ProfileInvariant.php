<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles\Rules;

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

use DCarbone\PHPFHIR\Profiles\ProfileConstraint;

/**
 * An invariant a profile declares on an element.  Carried into the generated class as metadata only.
 */
class ProfileInvariant
{
    private readonly string $path;
    private readonly ProfileConstraint $constraint;

    /**
     * @param string $path Element path relative to the profiled type.
     */
    public function __construct(string $path, ProfileConstraint $constraint)
    {
        $this->path = $path;
        $this->constraint = $constraint;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getConstraint(): ProfileConstraint
    {
        return $this->constraint;
    }

    /**
     * Shape of the invariant as written into the generated class.
     *
     * @return array{key: string, severity: string, human: string, expression: null|string, path: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->constraint->getKey(),
            'severity' => $this->constraint->getSeverity(),
            'human' => $this->constraint->getHuman(),
            'expression' => $this->constraint->getExpression(),
            'path' => $this->path,
        ];
    }
}
