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

trait SourceTrait
{
    /**
     * The raw element this type was parsed from.  Will be null for HTML and Undefined types
     *
     * @var null|\SimpleXMLElement
     */
    protected null|\SimpleXMLElement $sourceSXE;

    /**
     * Name of file in definition this type was parsed from
     * @var string
     */
    protected string $sourceFilename;

    /**
     * @return null|\SimpleXMLElement
     */
    public function getSourceSXE(): null|\SimpleXMLElement
    {
        return $this->sourceSXE;
    }

    /**
     * @return string
     */
    public function getSourceFilename(): string
    {
        return $this->sourceFilename;
    }

    /**
     * @return string
     */
    public function getSourceFileBasename(): string
    {
        return basename($this->getSourceFilename());
    }
}