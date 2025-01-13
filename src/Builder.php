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
    public function render(bool $coreFiles = true, null|array $versionNames = null): void
    {
        // register custom error handler to force explosions.
        set_error_handler(function ($errNum, $errStr, $errFile, $errLine) {
            throw new \ErrorException($errStr, $errNum, 1, $errFile, $errLine);
        });

        // write php-fhir core files
        if ($coreFiles) {
            $this->writeCoreFiles($this->config->getCoreFiles(), ['config' => $this->config]);
        }

        // if null, default to all versions.
        if (null === $versionNames) {
            $versionNames = $this->config->getVersionNames();
        }

        $this->writeFHIRVersionCoreFiles(...$versionNames);

        // write fhir version files
        $this->writeFHIRVersionTypeClasses(...$versionNames);

        // write fhir version test classes
        $this->writeFHIRVersionTestFiles(...$versionNames);
    }

    public function writeFHIRVersionCoreFiles(string ...$versionNames): void
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
            $this->writeCoreFiles($version->getCoreFiles(), ['version' => $version]);

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

            $log->startBreak("FHIR Version {$version->getName()} Type Class Generation");

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
                    $classDefinition = Templates::renderVersionXHTMLTypeClass($version, $types, $type);
                } else {
                    $classDefinition = Templates::renderVersionTypeClass($version, $types, $type);
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
            $log->endBreak("FHIR Version {$version->getName()} Type Class Generation");
        }
    }

    /**
     * Generate Test classes only.  Tests will not pass if FHIR classes have not been built.
     *
     * @throws \Exception
     */
    public function writeFHIRVersionTestFiles(string ...$versionNames): void
    {
//        $log = $this->config->getLogger();
//
//        foreach ($versionNames as $versionName) {
//            $version = $this->config->getVersion($versionName);
//
//            $log->startBreak(sprintf('FHIR Version %s Test Generation', $version->getName()));
//
//            $definition = $version->getDefinition();
//
//            if (!$definition->isDefined()) {
//                $log->startBreak('XSD Parsing');
//                $definition->buildDefinition();
//                $log->endBreak('XSD Parsing');
//            }
//
//            $types = $definition->getTypes();
//
//            $log->startBreak('Test Class Generation');
//
//            $testTypes = [
//                TestTypeEnum::UNIT,
//                TestTypeEnum::INTEGRATION,
////                TestTypeEnum::VALIDATION,
//            ];
//            foreach ($types->getIterator() as $type) {
//                if ($type->isAbstract()) {
//                    continue;
//                }
//
//                foreach ($testTypes as $testType) {
//                    // only render integration and validation tests if this is a "resource" type
//                    if (!$type->isResourceType() && $testType->isOneOf(TestTypeEnum::INTEGRATION, TestTypeEnum::VALIDATION)) {
//                        continue;
//                    }
//
//                    $log->debug("Generated {$testType->value} test class for type {$type}...");
//                    $classDefinition = Templates::renderVersionTypeClassTest($version, $types, $type, $testType);
//                    $filepath = FileUtils::buildTypeTestFilePath($version, $type, $testType);
//                    if (false === file_put_contents($filepath, $classDefinition)) {
//                        throw new \RuntimeException(
//                            sprintf(
//                                'Unable to write Type %s class definition to file %s',
//                                $filepath,
//                                $type
//                            )
//                        );
//                    }
//                }
//            }
//            $log->endBreak(sprintf('FHIR Version %s Test Generation', $version->getName()));
//        }
    }

    /**
     * Renders core PHP FHIR type classes, interfaces, traits, and enums.
     *
     * @param \DCarbone\PHPFHIR\CoreFiles $coreFiles
     * @param array $templateArgs
     * @return void
     */
    public function writeCoreFiles(CoreFiles $coreFiles, array $templateArgs): void
    {
        $this->log->startBreak('Core Files');

        // render each core file
        foreach ($coreFiles->getIterator() as $coreFile) {
            FileUtils::mkdirRecurse($coreFile->getFilepath());
            $this->writeFile(
                $coreFile->getFilepath(),
                Templates::renderCoreFile($this->config, $coreFile, $templateArgs),
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
