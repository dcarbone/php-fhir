<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Composite\CompositeTypes;
use DCarbone\PHPFHIR\Render\Templates;
use DCarbone\PHPFHIR\Utilities\FileUtils;

/**
 * Class Builder
 * @package DCarbone\PHPFHIR
 */
class Builder
{
    /** @var \DCarbone\PHPFHIR\Config */
    protected Config $config;

    /** @var \DCarbone\PHPFHIR\Logger */
    private Logger $log;

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->log = $config->getLogger();
    }

    /**
     * Generate FHIR object classes based on XSD
     *
     * @param bool $coreFiles If true, will generate core PHPFHIR classes, interfaces, traits, and enums.
     * @param null|array $versionNames Array of version names to limit generation to. If null, all configured versions will be generated.
     * @throws \ErrorException
     * @throws \Exception
     */
    public function render(bool       $coreFiles = true,
                           bool       $testFiles = true,
                           null|array $versionNames = null): void
    {
        // register custom error handler to force explosions.
        set_error_handler(function ($errNum, $errStr, $errFile, $errLine) {
            throw new \ErrorException($errStr, $errNum, 1, $errFile, $errLine);
        });

        // write php-fhir core entities
        if ($coreFiles) {
            $this->writeLibraryCoreEntities();
            $this->writeLibraryCoreCompositeTypeEntities(...$this->config->getVersionNames());
            $this->writeLibraryTestClasses();
        }

        // if null, default to all versions.
        if (null === $versionNames) {
            $versionNames = $this->config->getVersionNames();
        }

        // write fhir version core entities
        $this->writeFHIRVersionCoreEntities(...$versionNames);

        // write fhir version type classes
        $this->writeFHIRVersionTypeClasses(...$versionNames);

        if ($testFiles) {
            // write fhir version test classes
            $this->writeFHIRVersionTestClasses(...$versionNames);

            // write fhir version type test classes
            $this->writeFHIRVersionTypeTestClasses(...$versionNames);
        }
    }

    public function writeLibraryCoreEntities(): void
    {
        $this->writeCoreFiles($this->config->getCoreFiles(), ['config' => $this->config]);
    }

    public function writeFHIRVersionCoreEntities(string ...$versionNames): void
    {
        $log = $this->config->getLogger();

        foreach ($versionNames as $versionName) {
            $version = $this->config->getVersion($versionName);

            $log->startBreak("FHIR Version {$version->getName()} Core File Generation");

            $definition = $version->getDefinition();

            if (!$definition->isDefined()) {
                $log->startBreak("Parsing XSD Source for {$version->getName()}");
                $definition->buildDefinition();
                $log->endBreak("Parsing XSD Source for {$version->getName()}");
            }

            // write version core files
            $this->writeCoreFiles($version->getVersionCoreFiles(), ['version' => $version]);

            $log->endBreak("FHIR Version {$version->getName()} Core File Generation");
        }
    }

    /**
     * Generate FHIR version files.
     *
     * @throws \Exception
     */
    public function writeFHIRVersionTypeClasses(string ...$versionNames): void
    {
        $log = $this->config->getLogger();

        foreach ($versionNames as $versionName) {
            $version = $this->config->getVersion($versionName);

            $log->startBreak(sprintf('FHIR Version %s Type Class Generation', $version->getName()));

            // write version fhir type files
            $definition = $version->getDefinition();

            if (!$definition->isDefined()) {
                $log->startBreak("Parsing XSD Source for {$version->getName()}");
                $definition->buildDefinition();
                $log->endBreak("Parsing XSD Source for {$version->getName()}");
            }

            $types = $definition->getTypes();

            foreach ($types->getIterator() as $type) {
                /** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
                $log->debug("Generating class for type {$type}...");

                // TODO(@dcarbone): revisit with template system refactor
                if (PHPFHIR_XHTML_TYPE_NAME === $type->getFHIRName()) {
                    $classDefinition = Templates::renderVersionXHTMLTypeClass($version, $type);
                } else {
                    $classDefinition = Templates::renderVersionTypeClass($version, $type);
                }
                $filepath = FileUtils::buildTypeClassFilepath($version, $type);
                FileUtils::mkdirRecurse($filepath);
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
            $log->endBreak(sprintf('FHIR Version %s Type Class Generation', $version->getName()));
        }
    }

    public function writeLibraryCoreCompositeTypeEntities(string ...$versionNames): void
    {
        $log = $this->config->getLogger();

        $log->startBreak('Building composite types');

        $cts = new CompositeTypes();

        foreach($versionNames as $versionName) {
            $version = $this->config->getVersion($versionName);
            $definition = $version->getDefinition();
            if (!$definition->isDefined()) {
                $definition->buildDefinition();
            }
            foreach($definition->getTypes()->getIterator() as $type) {
                $cts->addType($version, $type);
            }
        }

        var_dump(count($cts));

        foreach($cts->getIterator() as $ct) {
            $ct->compile();
        }

        $log->endBreak('Building composite types');
    }

    /**
     * Generate test classes for core library entities
     *
     * @return void
     */
    public function writeLibraryTestClasses(): void
    {
        $this->writeCoreFiles($this->config->getCoreTestFiles(), ['config' => $this->config]);
    }

    /**
     * Generate test classes for FHIR version core entities.
     *
     * @param string ...$versionNames
     * @return void
     */
    public function writeFHIRVersionTestClasses(string ...$versionNames): void
    {
        $log = $this->config->getLogger();

        foreach ($versionNames as $versionName) {
            $version = $this->config->getVersion($versionName);

            $log->startBreak(sprintf('FHIR Version %s Test Class Generation', $version->getName()));

            $this->writeCoreFiles($version->getVersionTestCoreFiles(), ['version' => $version]);

            $log->endBreak(sprintf('FHIR Version %s Test Class Generation', $version->getName()));
        }
    }

    /**
     * Generate FHIR version type test classes.
     *
     * @throws \Exception
     */
    public function writeFHIRVersionTypeTestClasses(string ...$versionNames): void
    {
        $log = $this->config->getLogger();

        foreach ($versionNames as $versionName) {
            $version = $this->config->getVersion($versionName);

            $log->startBreak(sprintf('FHIR Version %s Types Test Generation', $version->getName()));

            $definition = $version->getDefinition();

            if (!$definition->isDefined()) {
                $log->startBreak('XSD Parsing');
                $definition->buildDefinition();
                $log->endBreak('XSD Parsing');
            }

            $types = $definition->getTypes();

            foreach ($types->getIterator() as $type) {
                /** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
                if ($type->isAbstract()) {
                    continue;
                }

                $filepath = FileUtils::buildTypeTestClassFilepath($version, $type);
                FileUtils::mkdirRecurse($filepath);
                $classDefinition = Templates::renderVersionTypeClassTest($version, $type);
                $this->writeFile($filepath, $classDefinition);
            }
            $log->endBreak(sprintf('FHIR Version %s Test Generation', $version->getName()));
        }
    }

    /**
     * Renders core PHP FHIR type classes, interfaces, traits, and enums.
     *
     * @param \DCarbone\PHPFHIR\CoreFiles $coreFiles
     * @param array $kwargs
     * @return void
     */
    public function writeCoreFiles(CoreFiles $coreFiles, array $kwargs): void
    {
        $this->log->startBreak('Core Files');

        // render each core file
        foreach ($coreFiles->getIterator() as $coreFile) {
            FileUtils::mkdirRecurse($coreFile->getFilepath());
            $this->writeFile(
                $coreFile->getFilepath(),
                Templates::renderCoreFile($this->config, $coreFile, $kwargs),
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
            throw new \RuntimeException(
                sprintf(
                    'Unable to write "%s"',
                    $filePath
                )
            );
        }
        $this->log->debug(sprintf('%d bytes written to file %s', $b, $filePath));
    }
}
