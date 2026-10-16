<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Profiles\DefinitionTypeNavigator;
use DCarbone\PHPFHIR\Profiles\ProfileClasses;
use DCarbone\PHPFHIR\Profiles\ProfilePackage;
use DCarbone\PHPFHIR\Profiles\ProfilePackageReader;
use DCarbone\PHPFHIR\Profiles\ProfileSet;
use DCarbone\PHPFHIR\Profiles\ProfileWriter;
use DCarbone\PHPFHIR\Profiles\ValueSetExpander;
use DCarbone\PHPFHIR\Render\Templates;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
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
     * @param bool $profiles If true, will generate profile classes for versions that have profile packages configured.
     * @param null|array $profilePackages Array of profile package names to limit profile generation to. If null, all configured packages will be generated.
     * @throws \ErrorException
     * @throws \Exception
     */
    public function render(bool       $coreFiles = true,
                           bool       $testFiles = true,
                           null|array $versionNames = null,
                           bool       $profiles = true,
                           null|array $profilePackages = null): void
    {
        $this->registerErrorHandler();

        // write php-fhir core entities
        if ($coreFiles) {
            $this->writeLibraryCoreEntities();
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

        // write fhir version profile classes
        if ($profiles) {
            $this->renderProfiles($versionNames, $profilePackages);
        }
    }

    /**
     * Generate profile classes for the configured profile packages of the provided versions.  Versions without any
     * configured profile packages are skipped.
     *
     * @param null|array $versionNames Array of version names to limit generation to. If null, all configured versions will be generated.
     * @param null|array $packageNames Array of profile package names to limit generation to. If null, all configured packages will be generated.
     * @throws \InvalidArgumentException If a specified package is not configured for any of the specified versions.
     */
    public function renderProfiles(null|array $versionNames = null, null|array $packageNames = null): void
    {
        $this->registerErrorHandler();

        if (null === $versionNames) {
            $versionNames = $this->config->getVersionNames();
        }

        $this->assertProfilePackagesConfigured($versionNames, $packageNames);

        $log = $this->config->getLogger();

        foreach ($versionNames as $versionName) {
            $version = $this->config->getVersion($versionName);
            $profilesConfig = $version->getVersionConfig()->getProfilesConfig();
            if (!$profilesConfig->hasPackages()) {
                continue;
            }

            $log->startBreak(sprintf('FHIR Version %s Profile Class Generation', $versionName));

            $definition = $version->getDefinition();
            if (!$definition->isDefined()) {
                $log->startBreak("Parsing XSD Source for {$versionName}");
                $definition->buildDefinition();
                $log->endBreak("Parsing XSD Source for {$versionName}");
            }

            $profilesNamespace = $version->getFullyQualifiedName(false, $profilesConfig->getNamespace());
            $fhirVersion = $version->getSourceMetadata()->getSemanticVersion(true);

            $ordered = $profilesConfig->getOrderedPackages();

            // packages that were asked for are written together with everything they depend on, as their classes extend
            // the dependencies' classes.
            $writePackages = null;
            if (null !== $packageNames) {
                $writePackages = [];
                foreach ($packageNames as $requested) {
                    if ($profilesConfig->hasPackage($requested)) {
                        $writePackages = [...$writePackages, $requested, ...$profilesConfig->getDependencyClosure($requested)];
                    }
                }
                $writePackages = array_values(array_unique($writePackages));
            }

            $copyright = CopyrightUtils::compileFullCopyrightComment($this->config, $version->getSourceMetadata());
            $navigator = new DefinitionTypeNavigator($version);
            $index = ProfileWriter::newIndex();
            $support = [
                'matcher' => $this->config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALUE_MATCHER)->getFullyQualifiedName(false),
                'validator' => $this->config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR)->getFullyQualifiedName(false),
            ];

            // one package at a time, dependencies first.  Everything a later package cannot need is released as we go,
            // as large package sets are memory hungry.
            $reads = [];
            $classesByPackage = [];
            foreach ($ordered as $i => $package) {
                $name = $package->getName();
                $log->info(sprintf('Reading profile package "%s"...', $name));
                $dependencies = array_map(
                    static fn(string $depName): ProfilePackage => $reads[$depName],
                    $package->getDependencies(),
                );
                $reads[$name] = ProfilePackageReader::read($package, $fhirVersion, $dependencies);
                $set = ProfileSet::fromPackage($reads[$name], $package->isStrict());
                foreach ($set->getUnresolved() as $id => $base) {
                    $log->warning(sprintf(
                        'Profile "%s": base "%s" not found; extending the base type directly. Configure its package and add it to "dependencies" to fix.',
                        $id,
                        $base,
                    ));
                }

                $dependencyClasses = [];
                foreach ($profilesConfig->getDependencyClosure($name) as $depName) {
                    foreach ($classesByPackage[$depName] ?? [] as $url => $class) {
                        $dependencyClasses[$url] = $class;
                    }
                }
                $classes = ProfileClasses::build(
                    $set,
                    $profilesNamespace,
                    $package->getNamespace(),
                    static fn(string $typeName): null|string => $definition->getTypes()
                        ->getTypeByName($typeName)
                        ?->getFullyQualifiedClassName(false),
                    new ValueSetExpander($reads[$name]),
                    $dependencyClasses,
                    $navigator,
                    $support,
                );
                unset($set, $dependencyClasses);
                foreach ($classes as $class) {
                    foreach ($class->getSkipped() as $reason) {
                        $log->info(sprintf('Profile "%s": %s', $class->getProfile()->getId(), $reason));
                    }
                }
                $log->info(sprintf('Package "%s": %d profile classes', $name, count($classes)));

                ProfileWriter::writeClasses(
                    $this->config->getLibraryPath(),
                    $profilesNamespace,
                    $copyright,
                    $name,
                    $classes,
                    $index,
                    null === $writePackages || in_array($name, $writePackages, true),
                );

                $classesByPackage[$name] = $classes;
                unset($classes);

                // release whatever no later package depends on.
                $needed = [];
                foreach (array_slice($ordered, $i + 1) as $later) {
                    foreach ($profilesConfig->getDependencyClosure($later->getName()) as $depName) {
                        $needed[$depName] = true;
                    }
                }
                foreach (array_keys($reads) as $readName) {
                    if (!isset($needed[$readName])) {
                        unset($reads[$readName], $classesByPackage[$readName]);
                    }
                }
            }

            $enums = [];
            foreach ([
                PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE,
                PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE,
                PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH,
            ] as $entity) {
                $enums[$entity] = $this->config->getCoreFiles()->getCoreFileByEntityName($entity)->getFullyQualifiedName(false);
            }

            ProfileWriter::writeSupport($this->config->getLibraryPath(), $profilesNamespace, $copyright, $index, $enums);

            $log->endBreak(sprintf('FHIR Version %s Profile Class Generation', $versionName));
        }
    }

    /**
     * @param string[] $versionNames
     * @param null|string[] $packageNames
     * @throws \InvalidArgumentException
     */
    public function assertProfilePackagesConfigured(array $versionNames, null|array $packageNames): void
    {
        if (null === $packageNames) {
            return;
        }
        foreach ($packageNames as $packageName) {
            foreach ($versionNames as $versionName) {
                $profilesConfig = $this->config->getVersion($versionName)->getVersionConfig()->getProfilesConfig();
                if ($profilesConfig->hasPackage($packageName)) {
                    continue 2;
                }
            }
            throw new \InvalidArgumentException(sprintf(
                'Profile package "%s" is not configured for any of the specified versions: %s',
                $packageName,
                implode(', ', $versionNames),
            ));
        }
    }

    /**
     * Register custom error handler to force explosions.
     */
    private function registerErrorHandler(): void
    {
        set_error_handler(function ($errNum, $errStr, $errFile, $errLine) {
            throw new \ErrorException($errStr, $errNum, 1, $errFile, $errLine);
        });
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
