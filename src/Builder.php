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
     * Generate FHIR object classes based on XSD
     * @throws \ErrorException
     */
    public function render(): void
    {
        $this->prerender();

        $this->writeCoreTypeFiles();

        $this->writeFhirTypeFiles();

        if (!$this->config->isSkipTests()) {
            $this->writeFhirTestFiles();
        }
    }

    /**
     * Generate FHIR classes only.
     * @throws \ErrorException
     */
    public function writeFhirTypeFiles(): void
    {
        // register custom error handler to force explosions.
        set_error_handler(function ($errNum, $errStr, $errFile, $errLine) {
            throw new \ErrorException($errStr, $errNum, 1, $errFile, $errLine);
        });

        $log = $this->config->getLogger();

        $this->prerender();

        $definition = $this->getDefinition();

        $types = $definition->getTypes();

        $log->startBreak('FHIR Class Generation');
        foreach ($types->getIterator() as $type) {
            $log->debug("Generating class for type {$type}...");

            // TODO(@dcarbone): revisit with template system refactor
            if (PHPFHIR_XHTML_TYPE_NAME === $type->getFHIRName()) {
                $classDefinition = Templates::renderXhtmlTypeClass($this->config, $types, $type);
            } else {
                $classDefinition = Templates::renderFhirTypeClass($this->config, $types, $type);
            }
            $filepath = FileUtils::buildTypeFilePath($this->config, $type);
            if (!file_put_contents($filepath, $classDefinition)) {
                throw new \RuntimeException(
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
    public function writeFhirTestFiles(): void
    {
        $log = $this->config->getLogger();

        $this->prerender();

        $definition = $this->getDefinition();
        $types = $definition->getTypes();

        $log->startBreak('Test Class Generation');

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
                if (PHPFHIR_TEST_TYPE_INTEGRATION === $testType && !$type->isResourceType()) {
                    continue;
                }

                $log->debug("Generated {$testType} test class for type {$type}...");
                $classDefinition = Templates::renderFhirTypeClassTest($this->config, $types, $type, $testType);
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
     * @return \RecursiveIteratorIterator
     */
    protected function getCoreTypeFileIterator(): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                PHPFHIR_TEMPLATE_CORE_DIR,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
            ),
        );
    }

    /**
     * TODO(@dcarbone): refactor generation system, too sloppy right now.
     *
     * Renders core PHP FHIR type classes, interfaces, traits, and enums.
     *
     * @return void
     */
    protected function writeCoreTypeFiles(): void
    {
        $this->log->startBreak('Core Files');

        // localize types
        $types = $this->getDefinition()->getTypes();

        // render each core file
        foreach($this->getCoreTypeFileIterator() as $fpath => $fi) {
            /** @var $fi \SplFileInfo */

            // get filename
            $fname = basename($fpath);
            // store "type"
            $ftype = substr($fname, 0, strpos($fname, '_'));
            // trim "type" and ".php"
            $fname = strstr(substr($fname, strpos($fname,'_') + 1), '.', true);
            // classname suffix
            $suffix = ucfirst($ftype);

            // define "default" namespace
            $ns = $this->config->getNamespace(true);

            if ('class' === $ftype) {
                // 'class' types do have suffix
                $suffix = '';
            } else if ('test' === $ftype) {
                // test classes have different namespace
                $ns = $this->config->getTestsNamespace(PHPFHIR_TEST_TYPE_BASE, true);
                // trim subtype
                $fname = substr($fname, strpos($fname, '_') + 1);
            }

            // construct class filename
            $cname = sprintf(
                'PHPFHIR%s%s',
                implode('', array_map('ucfirst', explode('_', $fname))),
                $suffix
            );

            // write file to disk
            $this->writeFile(
                FileUtils::buildGenericFilePath(
                    $this->config,
                    $ns,
                    $cname,
                ),
                Templates::renderCoreType($fpath, $this->config, $types)
            );
        }

        $this->log->endBreak('Core Files');
    }

    /**
     * @param string $filePath
     * @param string $fileContents
     */
    private function writeFile(string $filePath, string $fileContents): void
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
}
