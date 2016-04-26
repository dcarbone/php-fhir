<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    private $_elementName;

    /** @var ComplexClassTypesEnum|null */
    private $_classType;

    /** @var string */
    private $_className;

    /** @var string */
    private $_namespace;

    /** @var XSDMapEntry */
    private $_extendedElementMapEntry = null;

    /** @var array */
    private $_implementedInterfaces = array();

    /** @var BasePropertyTemplate[] */
    private $_properties = array();

    /** @var BaseMethodTemplate[] */
    private $_methods = array();

    /**
     * Constructor
     *
     * @param string $fhirElementName
     * @param string $className
     * @param string $namespace
     * @param ComplexClassTypesEnum $classType
     */
    public function __construct($fhirElementName,
                                $className,
                                $namespace,
                                ComplexClassTypesEnum $classType = null)
    {
        if (NameUtils::isValidClassName($className))
            $this->_className = $className;
        else
            throw new \InvalidArgumentException('Class Name "'.$className.'" is not valid.');

        if (NameUtils::isValidNSName($namespace))
            $this->_namespace = $namespace;
        else
            throw new \InvalidArgumentException('Namespace "' . $namespace . '" is not valid.');

        $this->_elementName = $fhirElementName;
        $this->_classType = $classType;
    }

    /**
     * @return string
     */
    public function getElementName()
    {
        return $this->_elementName;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->_className;
    }

    /**
     * @return ComplexClassTypesEnum|null
     */
    public function getClassType()
    {
        return $this->_classType;
    }

    /**
     * @param XSDMapEntry $mapEntry
     */
    public function setExtendedElementMapEntry(XSDMapEntry $mapEntry)
    {
        $this->_extendedElementMapEntry = $mapEntry;
    }

    /**
     * @return XSDMapEntry
     */
    public function getExtendedElementMapEntry()
    {
        return $this->_extendedElementMapEntry;
    }

    /**
     * @return string[]
     */
    public function getImplementedInterfaces()
    {
        return $this->_implementedInterfaces;
    }

    /**
     * @param string $interface
     */
    public function addImplementedInterface($interface)
    {
        if (!in_array($interface, $this->_implementedInterfaces, true))
            $this->_implementedInterfaces[] = $interface;
    }

    /**
     * @param string $interface
     * @return bool
     */
    public function implementsInterface($interface)
    {
        return in_array($interface, $this->_implementedInterfaces, true);
    }

    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Property\BasePropertyTemplate[]
     */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * @return \DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate[]
     */
    public function getMethods()
    {
        return $this->_methods;
    }

    /**
     * @param BasePropertyTemplate $property
     */
    public function addProperty(BasePropertyTemplate $property)
    {
        $this->_properties[$property->getName()] = $property;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return isset($this->_properties[$name]);
    }

    /**
     * @param string $name
     * @return BasePropertyTemplate
     */
    public function getProperty($name)
    {
        return $this->_properties[$name];
    }

    /**
     * @param BaseMethodTemplate $method
     */
    public function addMethod(BaseMethodTemplate $method)
    {
        $this->_methods[$method->getName()] = $method;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasMethod($name)
    {
        return isset($this->_methods[$name]);
    }

    /**
     * @param string $name
     * @return BaseMethodTemplate
     */
    public function getMethod($name)
    {
        return $this->_methods[$name];
    }

    /**
     * @param bool|true $leadingSlashes
     * @return string
     */
    public function compileFullyQualifiedClassName($leadingSlashes = true)
    {
        if ($leadingSlashes)
            return sprintf('\\%s\\%s', $this->getNamespace(), $this->getClassName());

        return sprintf('%s\\%s', $this->getNamespace(), $this->getClassName());
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
    public function compileTemplate()
    {
        $ns = $this->getNamespace();
        if ('' === $ns)
            $output = "<?php\n\n";
        else
            $output = sprintf("<?php namespace %s;\n\n", $ns);

        $output = sprintf(
            "%s%s\n\n%s",
            $output,
            CopyrightUtils::getFullPHPFHIRCopyrightComment(),
            $this->_compileUseStatements()
        );

        if ("\n\n" !== substr($output, -2))
            $output = sprintf("%s\n", $output);

        if (isset($this->documentation) && count($this->documentation) > 0)
        {
            $output = sprintf(
                "%s/**\n%s */\n",
                $output,
                $this->getDocBlockDocumentationFragment(1)
            );
        }

        if ($this->_extendedElementMapEntry)
        {
            $output = sprintf(
                '%sclass %s extends %s',
                $output,
                $this->getClassName(),
                $this->_extendedElementMapEntry->getClassName()
            );
        }
        else
        {
            $output = sprintf(
                '%sclass %s',
                $output,
                $this->getClassName()
            );
        }

        if (count($this->_implementedInterfaces) > 0)
        {
            $interfaces = array();
            foreach($this->_implementedInterfaces as $interface)
            {
                if (0 === strpos($interface, '\\') && 1 === substr_count($interface, '\\'))
                    $interfaces[] = $interface;
                else
                    $interfaces[] = ltrim(substr($interface, strrpos($interface, '\\')), '\\');
            }

            $output = sprintf('%s implements %s', $output, implode(', ', $interfaces));
        }

        $output = sprintf("%s\n{\n", $output);

        foreach($this->getProperties() as $property)
        {
            $output = sprintf('%s%s', $output, (string)$property);
        }

        foreach($this->getMethods() as $method)
        {
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

        $thisClassname = $this->compileFullyQualifiedClassName();
        $thisNamespace = $this->getNamespace();

        $usedClasses = array();
        if ($this->_extendedElementMapEntry)
        {
            $usedClasses[] = sprintf(
                '%s\\%s',
                $this->_extendedElementMapEntry->namespace,
                $this->_extendedElementMapEntry->className
            );
        }

        if (count($this->_implementedInterfaces) > 0)
        {
            foreach($this->_implementedInterfaces as $interface)
            {
                $usedClasses[] = $interface;
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

        $usedClasses = array_count_values($usedClasses);
        ksort($usedClasses);

        foreach($usedClasses as $usedClass=>$timesImported)
        {
            // Don't use yourself, dog...
            if ($usedClass === $thisClassname)
                continue;

            // If this class is already in the same namespace as this one...
            $remainder = str_replace(array($thisNamespace, '\\'), '', $usedClass);
            if (basename($usedClass) === $remainder)
                continue;

            $useStatement = sprintf("%suse %s;\n", $useStatement, ltrim($usedClass, "\\"));
        }

        return $useStatement;
    }
}