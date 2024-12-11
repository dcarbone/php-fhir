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

use DCarbone\PHPFHIR\Enum\TestType;
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
     * @throws \ErrorException
     */
    public function render(string ...$versionNames): void
    {
        if ([] === $versionNames) {
            $versionNames = $this->config->listVersions();
        }

        // write php-fhir core files
        $this->writeCoreFiles(
            $this->getCoreTemplateFileIterator(),
            $this->config->getClassesPath(),
            $this->config->getFullyQualifiedName(true),
            $this->config->getFullyQualifiedTestsName(TestType::BASE, true),
            ['config' => $this->config]
        );

        // write fhir version files
        $this->writeFhirVersionFiles(...$versionNames);

        if (!$this->config->isSkipTests()) {
            $this->writeFhirVersionTestFiles();
        }
    }

    /**
     * @return \RecursiveIteratorIterator
     */
    protected function getVersionCoreTemplateFileIterator(): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                PHPFHIR_TEMPLATE_VERSIONS_CORE_DIR,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
            )
        );
    }

    /**
     * Generate FHIR version files.
     *
     * @throws \ErrorException
     * @throws \Exception
     */
    public function writeFhirVersionFiles(string ...$versionNames): void
    {
        // register custom error handler to force explosions.
        set_error_handler(function ($errNum, $errStr, $errFile, $errLine) {
            throw new \ErrorException($errStr, $errNum, 1, $errFile, $errLine);
        });

        $log = $this->config->getLogger();

        foreach ($this->config->getVersionsIterator() as $version) {
            if (!in_array($version->getName(), $versionNames, true)) {
                continue;
            }

            $log->startBreak(sprintf('FHIR Version %s Class Generation', $version->getName()));

            // write version fhir type files
            $definition = $version->getDefinition();

            if (!$definition->isDefined()) {
                $log->startBreak('XSD Parsing');
                $definition->buildDefinition();
                $log->endBreak('XSD Parsing');
            }

            $types = $definition->getTypes();

            // write version core files
            $this->writeCoreFiles(
                $this->getVersionCoreTemplateFileIterator(),
                $version->getClassesPath(),
                $version->getFullyQualifiedName(true),
                $version->getFullyQualifiedTestsName(TestType::BASE, true),
                [
                    'version' => $version,
                    'types' => $definition->getTypes(),
                ]
            );

            foreach ($types->getIterator() as $type) {
                /** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
                $log->debug("Generating class for type {$type}...");

                // TODO(@dcarbone): revisit with template system refactor
                if (PHPFHIR_XHTML_TYPE_NAME === $type->getFHIRName()) {
                    $classDefinition = Templates::renderVersionXhtmlTypeClass($version, $types, $type);
                } else {
                    $classDefinition = Templates::renderVersionTypeClass($version, $types, $type);
                }
                $filepath = FileUtils::buildTypeFilePath($version, $type);
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
        }
        $log->endBreak('FHIR Class Generation');

        restore_error_handler();
    }

    /**
     * Generate Test classes only.  Tests will not pass if FHIR classes have not been built.
     *
     * @throws \Exception
     */
    public function writeFhirVersionTestFiles(string ...$versionNames): void
    {
        $log = $this->config->getLogger();

        foreach ($this->config->getVersionsIterator() as $version) {
            if (!in_array($version->getName(), $versionNames, true)) {
                continue;
            }

            $definition = $version->getDefinition();

            if (!$definition->isDefined()) {
                $log->startBreak('XSD Parsing');
                $definition->buildDefinition();
                $log->endBreak('XSD Parsing');
            }

            $types = $definition->getTypes();

            $log->startBreak('Test Class Generation');

            $testTypes = [TestType::UNIT];
            if (null !== $version->getTestEndpoint()) {
                $testTypes[] = TestType::INTEGRATION;
                $testTypes[] = TestType::VALIDATION;
            }
            foreach ($types->getIterator() as $type) {

                // skip "abstract" types
                if ($type->isAbstract()) {
                    continue;
                }

                foreach ($testTypes as $testType) {
                    // only render integration and validation tests if this is a "resource" type
                    if (!$type->isResourceType() && $testType->isOneOf(TestType::INTEGRATION, TestType::VALIDATION)) {
                        continue;
                    }

                    $log->debug("Generated {$testType->value} test class for type {$type}...");
                    $classDefinition = Templates::renderVersionTypeClassTest($version, $types, $type, $testType);
                    $filepath = FileUtils::buildTypeTestFilePath($version, $type, $testType);
                    if (false === file_put_contents($filepath, $classDefinition)) {
                        throw new \RuntimeException(
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
    }

    /**
     * @return \RecursiveIteratorIterator
     */
    protected function getCoreTemplateFileIterator(): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                PHPFHIR_TEMPLATE_CORE_DIR,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
            ),
        );
    }

    /**
     * Renders core PHP FHIR type classes, interfaces, traits, and enums.
     *
     * @param \RecursiveIteratorIterator $dirIterator
     * @param string $baseOutputDir
     * @param string $baseNS
     * @param string $testNS
     * @param array $templateArgs
     * @return void
     */
    protected function writeCoreFiles(
        \RecursiveIteratorIterator $dirIterator,
        string                     $baseOutputDir,
        string                     $baseNS,
        string                     $testNS,
        array                      $templateArgs,
    ): void
    {
        $this->log->startBreak('Core Files');

        // render each core file
        foreach ($dirIterator as $fpath => $fi) {
            /** @var $fi \SplFileInfo */

            // get filename
            $fname = basename($fpath);
            // store "type"
            $ftype = substr($fname, 0, strpos($fname, '_'));
            // trim "type" and ".php"
            $fname = strstr(substr($fname, strpos($fname, '_') + 1), '.', true);
            // classname suffix
            $suffix = ucfirst($ftype);

            // define "default" namespace
            $ns = $baseNS;

            if ('class' === $ftype) {
                // 'class' types do have suffix
                $suffix = '';
            } else if ('test' === $ftype) {
                // test classes have different namespace
                $ns = $testNS;
                // trim subtype
                $fname = substr($fname, strpos($fname, '_') + 1);
            }

            // construct class filename
            $cname = sprintf(
                '%s%s',
                implode('', array_map('classFilenameFormat', explode('_', $fname))),
                $suffix
            );

            // write file to disk
            $this->writeFile(
                FileUtils::buildCoreFilePath(
                    $baseOutputDir,
                    $ns,
                    $cname,
                ),
                Templates::renderCoreTemplate($fpath, $templateArgs)
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
