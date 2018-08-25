<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\FileUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;
use DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry;

/**
 * Class ClassTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class ClassTemplate extends AbstractTemplate
{
    /** @var string */
    private $elementName;

    /** @var \DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum|null */
    private $classType;

    /** @var string */
    private $className;

    /** @var string */
    private $namespace;

    /** @var \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry */
    private $extendedElementMapEntry = null;

    /** @var array */
    private $implementedInterfaces = [];

    /** @var \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate[] */
    private $properties = [];

    /** @var \DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate[] */
    private $methods = [];

    /** @var \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry */
    private $XSDMapEntry;

    /** @var array */
    private $imports = [];

    /**
     * ClassTemplate constructor.
     * @param string $fhirElementName
     * @param string $className
     * @param string $namespace
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $XSDMapEntry
     * @param \DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum|null $classType
     */
    public function __construct(
        $fhirElementName,
        $className,
        $namespace,
        XSDMapEntry $XSDMapEntry,
        ComplexClassTypesEnum $classType = null
    ) {
        if (NameUtils::isValidClassName($className)) {
            $this->className = $className;
        } else {
            throw new \InvalidArgumentException('Class Name "' . $className . '" is not valid.');
        }

        if (NameUtils::isValidNSName($namespace)) {
            $this->namespace = $namespace;
        } else {
            throw new \InvalidArgumentException('Namespace "' . $namespace . '" is not valid.');
        }

        $this->elementName = $fhirElementName;
        $this->classType = $classType;

        $this->XSDMapEntry = $XSDMapEntry;
    }

    /**
     * @return string
     */
    public function getElementName()
    {
        return $this->elementName;
    }

    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\Enum\ComplexClassTypesEnum|null
     */
    public function getClassType()
    {
        return $this->classType;
    }

    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry
     */
    public function getExtendedElementMapEntry()
    {
        return $this->extendedElementMapEntry;
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\XSDMap\XSDMapEntry $mapEntry
     */
    public function setExtendedElementMapEntry(XSDMapEntry $mapEntry)
    {
        $this->extendedElementMapEntry = $mapEntry;
        $this->XSDMapEntry->setExtendedMapEntry($mapEntry);
    }

    /**
     * @return string[]
     */
    public function getImplementedInterfaces()
    {
        return $this->implementedInterfaces;
    }

    /**
     * @param string $interface
     */
    public function addImplementedInterface($interface)
    {
        if (!in_array($interface, $this->implementedInterfaces, true)) {
            $this->implementedInterfaces[] = $interface;
        }
    }

    /**
     * @param string $interface
     * @return bool
     */
    public function implementsInterface($interface)
    {
        return in_array($interface, $this->implementedInterfaces, true);
    }

    /**
     * @param BasePropertyTemplate $property
     */
    public function addProperty(BasePropertyTemplate $property)
    {
        $this->XSDMapEntry->addProperty($property->getName(), $property->getFHIRElementType());
        $this->properties[$property->getName()] = $property;
    }

    /**
     * @param \DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate $method
     */
    public function addMethod(BaseMethodTemplate $method)
    {
        $this->methods[$method->getName()] = $method;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasMethod($name)
    {
        return isset($this->methods[$name]);
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate
     */
    public function getMethod($name)
    {
        return isset($this->methods[$name]) ? $this->methods[$name] : null;
    }

    /**
     * @return XSDMapEntry
     */
    public function getXSDMapEntry()
    {
        return $this->XSDMapEntry;
    }

    /**
     * Add a specific name (class, interface, etc.) to the use statements
     *
     * @param string $name
     */
    public function addImport($name)
    {
        $this->imports[] = $name;
    }

    /**
     * @param string $outputPath
     * @return bool
     */
    public function writeToFile($outputPath)
    {
        return (bool)file_put_contents(
            $this->compileFullOutputPath($outputPath),
            $this->compileTemplate()
        );
    }

    /**
     * @param string $outputPath
     * @return string
     */
    public function compileFullOutputPath($outputPath)
    {
        return sprintf('%s/%s/%s.php',
            $outputPath,
            FileUtils::buildDirPathFromNS($this->getNamespace()),
            $this->getClassName()
        );
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        $ns = $this->getNamespace();
        if ('' === $ns) {
            $output = "<?php\n\n";
        } else {
            $output = sprintf("<?php namespace %s;\n\n", $ns);
        }

        $output = sprintf(
            "%s%s\n\n%s",
            $output,
            CopyrightUtils::getFullPHPFHIRCopyrightComment(),
            $this->_compileUseStatements()
        );

        if ("\n\n" !== substr($output, -2)) {
            $output = sprintf("%s\n", $output);
        }

        if (isset($this->documentation) && count($this->documentation) > 0) {
            $output = sprintf(
                "%s/**\n%s */\n",
                $output,
                $this->getDocBlockDocumentationFragment(1)
            );
        }

        if ($this->extendedElementMapEntry) {
            $output = sprintf(
                '%sclass %s extends %s',
                $output,
                $this->getClassName(),
                $this->extendedElementMapEntry->getClassName()
            );
        } else {
            $output = sprintf(
                '%sclass %s',
                $output,
                $this->getClassName()
            );
        }

        if (count($this->implementedInterfaces) > 0) {
            $interfaces = array();
            foreach ($this->implementedInterfaces as $interface) {
                if (0 === strpos($interface, '\\') && 1 === substr_count($interface, '\\')) {
                    $interfaces[] = $interface;
                } else {
                    $interfaces[] = ltrim(substr($interface, strrpos($interface, '\\')), '\\');
                }
            }
            $output = sprintf('%s implements %s', $output, implode(', ', $interfaces));
        }

        $output = sprintf("%s\n{\n", $output);

        foreach ($this->getProperties() as $property) {
            $output = sprintf('%s%s', $output, (string)$property);
        }

        foreach ($this->getMethods() as $method) {
            $output = sprintf('%s%s', $output, (string)$method);
        }

        return sprintf("%s\n}", $output);
    }

    /**
     * @return string
     */
    private function _compileUseStatements()
    {
        $useStatement = '';

        $thisClassName = $this->compileFullyQualifiedClassName();
        $thisNamespace = $this->getNamespace();

        $imports = array();
        if ($this->extendedElementMapEntry) {
            $imports[] = sprintf(
                '%s\\%s',
                $this->extendedElementMapEntry->namespace,
                $this->extendedElementMapEntry->className
            );
        }

        if (count($this->implementedInterfaces) > 0) {
            foreach ($this->implementedInterfaces as $interface) {
                $imports[] = $interface;
            }
        }

        // TODO: The below may eventually be used for type-hinting.
//        foreach($this->_properties as $property)
//        {
//            $type = $property->getPhpType();
//            if (null === $type)
//                continue;
//
//            $usedClasses[] = $type;
//        }

        $imports = array_count_values(array_merge($this->getImports(), $imports));
        ksort($imports);

        foreach ($imports as $name => $timesImported) {
            // Don't import base namespace things.
            if (0 === strpos($name, '\\') && 1 === substr_count($name, '\\')) {
                continue;
            }

            // Don't use yourself, dog...
            if ($name === $thisClassName) {
                continue;
            }

            // If this class is already in the same namespace as this one...
            $remainder = str_replace(array($thisNamespace, '\\'), '', $name);
            if (basename($name) === $remainder) {
                continue;
            }

            $useStatement = sprintf("%suse %s;\n", $useStatement, ltrim($name, "\\"));
        }

        return $useStatement;
    }

    /**
     * @param bool|true $leadingSlashes
     * @return string
     */
    public function compileFullyQualifiedClassName($leadingSlashes = true)
    {
        if ($leadingSlashes) {
            return sprintf('\\%s\\%s', $this->getNamespace(), $this->getClassName());
        }

        return sprintf('%s\\%s', $this->getNamespace(), $this->getClassName());
    }

    /**
     * @return array
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @return bool
     */
    public function isPrimitive()
    {
        return $this->hasProperty('value') && $this->getProperty('value')->isPrimitive();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate
     */
    public function getProperty($name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }
}