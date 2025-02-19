<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Version;

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Builder;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Utilities\ImportUtils;
use DCarbone\PHPFHIR\Version;
use DCarbone\PHPFHIR\Version\Definition\TypeDecorationValidator;
use DCarbone\PHPFHIR\Version\Definition\TypeDecorator;
use DCarbone\PHPFHIR\Version\Definition\TypeExtractor;
use DCarbone\PHPFHIR\Version\Definition\TypePropertyDecorator;
use DCarbone\PHPFHIR\Version\Definition\Types;

/**
 * Class Definition
 * @package DCarbone\PHPFHIR
 */
class Definition
{
    /** @var \DCarbone\PHPFHIR\Config */
    private Config $_config;
    /** @var \DCarbone\PHPFHIR\Version */
    private Version $_version;

    /** @var \DCarbone\PHPFHIR\Version\Definition\Types|null */
    private null|Types $_types = null;

    /** @var \DCarbone\PHPFHIR\Builder */
    private Builder $_builder;

    /**
     * Definition constructor.
     * @param \DCarbone\PHPFHIR\Version $version
     */
    public function __construct(Version $version)
    {
        $this->_config = $version->getConfig();
        $this->_version = $version;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'types' => $this->_types,
        ];
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function buildDefinition(): void
    {
        if ($this->isDefined()) {
            return;
        }

        $log = $this->_config->getLogger();

        $log->startBreak('Extracting defined types');

        $log->info('Parsing types');
        $this->_types = TypeExtractor::parseTypes($this->_config, $this->_version);

        $log->info('Finding restriction base types');
        TypeDecorator::findRestrictionBaseTypes($this->_version, $this->_types);

        $log->info('Finding parent types');
        TypeDecorator::findParentTypes($this->_config, $this->_types);

        $log->info('Finding component types');
        TypeDecorator::findComponentOfTypes($this->_config, $this->_types);

        // TODO: order of operations issue here, ideally this would be first...
        $log->info('Determining type kinds');
        TypeDecorator::determineParsedTypeKinds($this->_version, $this->_types);

        $log->info('Determining Primitive Type kinds');
        TypeDecorator::determinePrimitiveTypes($this->_config, $this->_types);

        $log->info('Finding properties without names');
        TypeDecorator::findNamelessProperties($this->_config, $this->_types);

        $log->info('Finding property types');
        TypePropertyDecorator::findPropertyTypes($this->_config, $this->_types);

        $log->info('Manually setting some property names');
        TypePropertyDecorator::setMissingPropertyNames($this->_config, $this->_types);

        $log->info('Parsing union memberOf Types');
        TypeDecorator::parseUnionMemberTypes($this->_config, $this->_types);

        $log->info('Setting contained type flags');
        TypeDecorator::setContainedTypeFlag($this->_config, $this->_version, $this->_types);

        $log->info('Setting primitive container flags');
        TypeDecorator::setPrimitiveContainerFlag($this->_version, $this->_types);

        $log->info('Setting comment container flags');
        TypeDecorator::setCommentContainerFlag($this->_config, $this->_types);

        $log->info('Applying per-version overrides');
        TypeDecorator::applyVersionOverrides($this->_version, $this->_types);

        $log->info('Finding overloaded properties in child types');
        TypePropertyDecorator::findOverloadedProperties($this->_version, $this->_types);

        $log->info('Compiling type imports');
        TypeDecorator::buildTypeImports($this->_version, $this->_types);

        $log->info('Performing some sanity checking');
        TypeDecorationValidator::validateDecoration($this->_config, $this->_version, $this->_types);

        $log->info(count($this->_types) . ' types extracted.');
        $log->endBreak('Extracting defined types');
    }

    /**
     * @return \DCarbone\PHPFHIR\Config
     */
    public function getConfig(): Config
    {
        return $this->_config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version
     */
    public function getVersion(): Version
    {
        return $this->_version;
    }

    /**
     * @return \DCarbone\PHPFHIR\Version\Definition\Types
     * @throws \Exception
     */
    public function getTypes(): Types
    {
        if (!$this->isDefined()) {
            $this->buildDefinition();
        }
        return $this->_types;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isDefined(): bool
    {
        return isset($this->_types);
    }

    /**
     * @return \DCarbone\PHPFHIR\Builder
     */
    public function getBuilder(): Builder
    {
        if (!isset($this->_builder)) {
            $this->_builder = new Builder($this->_config);
        }
        return $this->_builder;
    }
}