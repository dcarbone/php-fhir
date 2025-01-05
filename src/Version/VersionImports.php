<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version;

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

use DCarbone\PHPFHIR\Builder\Imports;
use DCarbone\PHPFHIR\Version;

class VersionImports extends Imports
{
    /** @var \DCarbone\PHPFHIR\Version */
    private Version $_version;

    /**
     * @param \DCarbone\PHPFHIR\Version $version
     * @param string $localNamespace
     * @param string $localName
     */
    public function __construct(Version $version, string $localNamespace, string $localName)
    {
        parent::__construct($version->getConfig(), $localNamespace, $localName);

        $this->_version = $version;
    }

    public function addVersionCoreFileImport(string ...$entityNames): self
    {
        foreach ($entityNames as $en) {
            $coreFile = $this->_version->getCoreFiles()->getCoreFileByEntityName($en);
            $this->addImport($coreFile->getNamespace(), $coreFile->getEntityName());
        }
        return $this;
    }
}