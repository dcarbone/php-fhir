<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Render\Templates;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\FileUtils;
use RuntimeException;

/**
 * Class Builder
 * @package DCarbone\PHPFHIR
 */
class Builder
{
    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    protected VersionConfig $config;

    /** @var \DCarbone\PHPFHIR\Definition */
    protected Definition $definition;

    /** @var \DCarbone\PHPFHIR\Logger */
    private Logger $log;

    /** @var bool */
    private bool $preGenerationCompleted = false;

    /**
     * Generator constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition $definition
     */
    public function __construct(VersionConfig $config, Definition $definition)
    {
        $this->config = $config;
        $this->definition = $definition;
        $this->log = $config->getLogger();
    }


    /**
     * @return \DCarbone\PHPFHIR\Definition
     */
    public function getDefinition(): Definition
    {
        $log = $this->config->getLogger();

        if (!$this->definition->isDefined()) {
            $log->startBreak('XSD Parsing');
            $this->definition->buildDefinition();
            $log->endBreak('XSD Parsing');
        }

        return $this->definition;
    }

    /**
     * Generate FHIR classes only.
     * @throws \ErrorException
     */
    public function renderFHIRClasses(): void
    {
        set_error_handler(function ($errNum, $errStr, $errFile, $errLine) {
            throw new \ErrorException($errStr, $errNum, 1, $errFile, $errLine);
        });

        $log = $this->config->getLogger();

        $this->prerender();

        $definition = $this->getDefinition();

        $this->renderStaticClasses();

        $types = $definition->getTypes();

        $log->startBreak('FHIR Class Generation');
        foreach ($types->getIterator() as $type) {
            $log->debug("Generating class for type {$type}...");

            // TODO(@dcarbone): revisit with template system refactor
            if (PHPFHIR_XHTML_TYPE_NAME === $type->getFHIRName()) {
                $classDefinition = Templates::renderXhtmlTypeClass($this->config, $types, $type);
            } else {
                $classDefinition = Templates::renderTypeClass($this->config, $types, $type);
            }
            $filepath = FileUtils::buildTypeFilePath($this->config, $type);
            if (!file_put_contents($filepath, $classDefinition)) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to write Type %s class definition to file %s',
                        $filepath,
                        $type
                    )
                );
            }
        }
        $log->endBreak('FHIR Class Generation');

        restore_error_handler();
    }

    /**
     * Generate Test classes only.  Tests will not pass if FHIR classes have not been built.
     */
    public function renderTestClasses(): void
    {
        $log = $this->config->getLogger();

        $this->prerender();

        $definition = $this->getDefinition();
        $types = $definition->getTypes();

        $log->startBreak('Test Class Generation');

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getTestsNamespace(PHPFHIR_TEST_TYPE_BASE, true),
                PHPFHIR_TEST_CLASSNAME_CONSTANTS
            ),
            Templates::renderConstantsTestClass($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getTestsNamespace(PHPFHIR_TEST_TYPE_BASE, true),
                PHPFHIR_TEST_CLASSNAME_TYPEMAP
            ),
            Templates::renderTypeMapTestClass($this->config, $types)
        );

        $testTypes = [PHPFHIR_TEST_TYPE_UNIT];
        if (null !== $this->config->getTestEndpoint()) {
            $testTypes[] = PHPFHIR_TEST_TYPE_INTEGRATION;
        }
        foreach ($types->getIterator() as $type) {

            // skip "abstract" types
            if ($type->isAbstract()) {
                continue;
            }

            foreach ($testTypes as $testType) {
                // skip domain resources
                // TODO(@dcarbone): why did you do this.
                if (PHPFHIR_TEST_TYPE_INTEGRATION === $testType && !$type->isDomainResource()) {
                    continue;
                }

                $log->debug("Generated {$testType} test class for type {$type}...");
                $classDefinition = Templates::renderTypeTestClass($this->config, $types, $type, $testType);
                $filepath = FileUtils::buildTypeTestFilePath($this->config, $type, $testType);
                if (false === file_put_contents($filepath, $classDefinition)) {
                    throw new RuntimeException(
                        sprintf(
                            'Unable to write Type %s class definition to file %s',
                            $filepath,
                            $type
                        )
                    );
                }
            }
        }


        $log->endBreak('Test Class Generation');
    }

    /**
     * Generate FHIR object classes based on XSD
     * @throws \ErrorException
     */
    public function render(): void
    {
        $this->prerender();

        $this->renderFHIRClasses();

        if (!$this->config->isSkipTests()) {
            $this->renderTestClasses();
        }

        $this->renderStaticClasses();
    }

    /**
     * Commands to run prior to class generation
     */
    protected function prerender(): void
    {
        // Initialize some classes and things.
        if (!$this->preGenerationCompleted) {
            $this->log->startBreak('Prerender');
            $this->log->info('Compiling Copyrights...');
            CopyrightUtils::compileCopyrights($this->config);
            $this->log->endBreak('Prerender');
            $this->preGenerationCompleted = true;
        }
    }

    /**
     * @param string $filePath
     * @param string $fileContents
     */
    private function writeClassFile(string $filePath, string $fileContents): void
    {
        $this->log->info(sprintf('Writing %s...', $filePath));
        $b = file_put_contents($filePath, $fileContents);
        if (false === $b) {
            throw new RuntimeException(
                sprintf(
                    'Unable to write "%s"',
                    $filePath
                )
            );
        }
        $this->log->debug(sprintf('%d bytes written to file %s', $b, $filePath));
    }

    protected function renderStaticClasses(): void
    {
        $types = $this->definition->getTypes();

        // Constants class
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_CONSTANTS
            ),
            Templates::renderConstants($this->config, $types)
        );

        // TypeMap class
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_TYPEMAP
            ),
            Templates::renderTypeMapClass($this->config, $types)
        );

        // Autoloader class
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_AUTOLOADER
            ),
            Templates::renderAutoloaderClass($this->config, $types)
        );

        // FHIRType interface
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_INTERFACE_TYPE
            ),
            Templates::renderPHPFHIRTypeInterface($this->config, $types)
        );

        // ContainedType interface
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_INTERFACE_CONTAINED_TYPE
            ),
            Templates::renderPHPFHIRContainedTypeInterface($this->config, $types)
        );

        // CommentContainer interface
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_INTERFACE_COMMENT_CONTAINER
            ),
            Templates::renderPHPFHIRCommentContainerInterface($this->config, $types)
        );

        // CommentContainer trait
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_TRAIT_COMMENT_CONTAINER
            ),
            Templates::renderPHPFHIRCommentContainerTrait($this->config, $types)
        );

        // ValidationAssertions trait
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_TRAIT_VALIDATION_ASSERTIONS
            ),
            Templates::renderPHPFHIRValidationAssertionsTrait($this->config, $types)
        );

        // ChangeTracking trait
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_TRAIT_CHANGE_TRACKING
            ),
            Templates::renderPHPFHIRChangeTrackingTrait($this->config, $types)
        );

        // XMLNS trait
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_TRAIT_XMLNS
            ),
            Templates::renderPHPFHIRXMLNamespaceTrait($this->config, $types)
        );

        // ResponseParser config class
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG
            ),
            Templates::renderPHPFHIRResponseParserConfigClass($this->config, $types)
        );

        // ResponseParser class
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_RESPONSE_PARSER
            ),
            Templates::renderPHPFHIRResponseParserClass($this->config, $types)
        );
    }
}
