<?php namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
use DCarbone\PHPFHIR\Generator\TemplateBuilder;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\FileUtils;

/**
 * Class Builder
 * @package DCarbone\PHPFHIR
 */
class Builder
{
    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    protected $config;

    /** @var \DCarbone\PHPFHIR\Definition */
    protected $definition;

    /** @var \DCarbone\PHPFHIR\Logger */
    private $log;

    /** @var bool */
    private $preGenerationCompleted = false;

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
    public function getDefinition()
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
     */
    public function buildFHIRClasses()
    {
        $log = $this->config->getLogger();

        $this->beforeGeneration();

        $definition = $this->getDefinition();

        $this->staticClassGeneration();

        $types = $definition->getTypes();

        $log->startBreak('FHIR Class Generation');
        foreach ($types->getIterator() as $type) {
            $log->debug("Generating class for type {$type}...");
            $classDefinition = TemplateBuilder::generateTypeClass($this->config, $types, $type);
            if (null !== $classDefinition) {
                $filepath = FileUtils::buildTypeFilePath($this->config, $type);
                if (!(bool)file_put_contents($filepath, $classDefinition)) {
                    throw new \RuntimeException(sprintf(
                        'Unable to write Type %s class definition to file %s',
                        $filepath,
                        $type
                    ));
                }
            } else {
                $log->warning(sprintf(
                    'Received NULL from generateTypeClass call for type "%s"...',
                    $type
                ));
            }
        }
        $log->endBreak('FHIR Class Generation');
    }

    /**
     * Generate Test classes only.  Tests will not pass if FHIR classes have not been built.
     */
    public function buildTestClasses()
    {
        $log = $this->config->getLogger();

        $this->beforeGeneration();

        $definition = $this->getDefinition();
        $types = $definition->getTypes();

        $log->startBreak('Test Class Generation');
        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getTestsNamespace(true),
                PHPFHIR_TEST_CLASSNAME_CONSTANTS
            ),
            TemplateBuilder::generateConstantsTestClass($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getTestsNamespace(true),
                PHPFHIR_TEST_CLASSNAME_TYPEMAP
            ),
            TemplateBuilder::generateTypeMapTestClass($this->config, $types)
        );

        foreach ($types->getIterator() as $type) {
            $log->debug("Generated test class for type {$type}...");
            $wrapperDefinition = TemplateBuilder::generateTypeTestWrapperFile($this->config, $types, $type);
            $filepath = FileUtils::buildTypeTestFilePath($this->config, $type);
            if (!(bool)file_put_contents($filepath, $wrapperDefinition)) {
                throw new \RuntimeException(sprintf(
                    'Unable to write Type %s test wrapper file %s',
                    $filepath,
                    $type
                ));
            }

            $gte8ClassDefinition = TemplateBuilder::generateTypeTestClass($this->config, $types, $type, true);
            $filepath = FileUtils::buildPHPUnitVersionedTestFilePath($this->config, $type, true);
            if (!(bool)file_put_contents($filepath, $gte8ClassDefinition)) {
                throw new \RuntimeException(sprintf(
                    'Unable to write Type %s test for phpunit >= 8 class definition to file %s',
                    $filepath,
                    $type
                ));
            }

            $lt8ClassDefinition = TemplateBuilder::generateTypeTestClass($this->config, $types, $type, false);
            $filepath = FileUtils::buildPHPUnitVersionedTestFilePath($this->config, $type, false);
            if (!(bool)file_put_contents($filepath, $lt8ClassDefinition)) {
                throw new \RuntimeException(sprintf(
                    'Unable to write Type %s test for phpunit < 8 class definition to file %s',
                    $filepath,
                    $type
                ));
            }
        }

        $log->endBreak('Test Class Generation');
    }

    /**
     * Generate FHIR object classes based on XSD
     */
    public function build()
    {
        $this->beforeGeneration();

        $this->buildFHIRClasses();

        if (!$this->config->isSkipTests()) {
            $this->buildTestClasses();
        }

        $this->staticClassGeneration();
    }

    /**
     * Commands to run prior to class generation
     */
    protected function beforeGeneration()
    {
        // Initialize some classes and things.
        if (!$this->preGenerationCompleted) {
            $this->log->startBreak('Generator Class Initialization');
            $this->log->info('Compiling Copyrights...');
            CopyrightUtils::compileCopyrights($this->config);
            $this->log->endBreak('Generator Class Initialization');
            $this->preGenerationCompleted = true;
        }
    }

    /**
     * @param string $filePath
     * @param string $fileContents
     */
    private function writeClassFile($filePath, $fileContents)
    {
        $this->log->info(sprintf('Writing %s...', $filePath));
        $b = file_put_contents($filePath, $fileContents);
        if (false === $b) {
            throw new \RuntimeException(sprintf(
                'Unable to write "%s"',
                $filePath
            ));
        }
        $this->log->debug(sprintf('%d bytes written to file %s', $b, $filePath));
    }

    /**
     * Commands to run after class generation
     */
    protected function staticClassGeneration()
    {
        $types = $this->definition->getTypes();

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_CONSTANTS
            ),
            TemplateBuilder::generateConstants($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_TYPEMAP
            ),
            TemplateBuilder::generateTypeMapClass($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_AUTOLOADER
            ),
            TemplateBuilder::generateAutoloaderClass($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_INTERFACE_TYPE
            ),
            TemplateBuilder::generatePHPFHIRTypeInterface($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_INTERFACE_CONTAINED_TYPE
            ),
            TemplateBuilder::generatePHPFHIRContainedTypeInterface($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_INTERFACE_COMMENT_CONTAINER
            ),
            TemplateBuilder::generatePHPFHIRCommentContainerInterface($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_TRAIT_COMMENT_CONTAINER
            ),
            TemplateBuilder::generatePHPFHIRCommentContainerTrait($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG
            ),
            TemplateBuilder::generatePHPFHIRResponseParserConfigClass($this->config, $types)
        );

        $this->writeClassFile(
            FileUtils::buildGenericFilePath(
                $this->config,
                $this->config->getNamespace(true),
                PHPFHIR_CLASSNAME_RESPONSE_PARSER
            ),
            TemplateBuilder::generatePHPFHIRResponseParserClass($this->config, $types)
        );
    }
}
