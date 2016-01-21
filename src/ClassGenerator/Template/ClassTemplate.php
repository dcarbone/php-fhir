<?php namespace PHPFHIR\ClassGenerator\Template;

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

use PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;
use PHPFHIR\ClassGenerator\Utilities\FileUtils;
use PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class ClassTemplate
 * @package PHPFHIR\ClassGenerator\Template
 */
class ClassTemplate extends AbstractTemplate
{
    /** @var string */
    private $_elementName;

    /** @var string */
    private $_className;

    /** @var string */
    private $_pseudonym;

    /** @var string */
    private $_namespace;

    /** @var array */
    private $_uses = array();

    /** @var string */
    private $_extendedElementName;

    /** @var string */
    private $_extendedClassName;

    /** @var PropertyTemplate[] */
    private $_properties = array();

    /** @var AbstractMethodTemplate[] */
    private $_methods = array();

    /**
     * Constructor
     *
     * @param string $elementName
     * @param string $className
     * @param string $namespace
     * @param string $pseudonym
     */
    public function __construct($elementName, $className, $namespace, $pseudonym)
    {
        if (NameUtils::isValidClassName($className))
            $this->_className = $className;
        else
            throw new \InvalidArgumentException('Class Name "'.$className.'" is not valid.');

        if (NameUtils::isValidNSName($namespace))
            $this->_namespace = $namespace;
        else
            throw new \InvalidArgumentException('Namespace "' . $namespace . '" is not valid.');

        if (NameUtils::isValidClassName($pseudonym))
            $this->_pseudonym = $pseudonym;
        else
            throw new \InvalidArgumentException('Class Pseudonym "'.$pseudonym.'" is not valid.');

        $this->_elementName = $elementName;
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
    public function getUseStatement()
    {
        return sprintf('%s\\%s;', $this->_namespace, $this->_className);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * @return array
     */
    public function getUses()
    {
        return $this->_uses;
    }

    /**
     * @param string $use
     */
    public function addUse($use)
    {
        $this->_uses[] = $use;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->_className;
    }

    /**
     * @return string
     */
    public function getPseudonym()
    {
        return $this->_pseudonym;
    }

    /**
     * @param string $extendedClassName
     */
    public function setExtendedClassName($extendedClassName)
    {
        $this->_extendedClassName = $extendedClassName;
    }

    /**
     * @return string
     */
    public function getExtendedClassName()
    {
        return $this->_extendedClassName;
    }

    /**
     * @param string $extendedElementName
     */
    public function setExtendedElementName($extendedElementName)
    {
        $this->_extendedElementName = $extendedElementName;
    }

    /**
     * @return string
     */
    public function getExtendedElementName()
    {
        return $this->_extendedElementName;
    }

    /**
     * @return \PHPFHIR\ClassGenerator\Template\PropertyTemplate[]
     */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * @return \PHPFHIR\ClassGenerator\Template\AbstractMethodTemplate[]
     */
    public function getMethods()
    {
        return $this->_methods;
    }

    /**
     * @param PropertyTemplate $property
     */
    public function addProperty(PropertyTemplate $property)
    {
        $this->_properties[$property->getName()] = $property;
    }

    /**
     * @param AbstractMethodTemplate $method
     */
    public function addMethod(AbstractMethodTemplate $method)
    {
        $this->_methods[$method->getName()] = $method;
    }

    /**
     * @param bool|true $leadingSlashes
     * @return string
     */
    public function getFullyQualifiedClassName($leadingSlashes = true)
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

        if ($this->_extendedClassName)
        {
            $output = sprintf(
                "%sclass %s extends %s\n",
                $output,
                $this->getClassName(),
                $this->getExtendedClassName()
            );
        }
        else
        {
            $output = sprintf(
                "%sclass %s\n",
                $output,
                $this->getClassName()
            );
        }

        $output = sprintf("%s{\n", $output);

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

        $thisClassName = $this->getClassName();
        $usedClasses = array();
        foreach(array_unique($this->getUses()) as $use)
        {
            $usedClasses[] = $use;
        }

        $usedClasses = array_count_values($usedClasses);
        ksort($usedClasses);

        foreach($usedClasses as $usedClass=>$timesImported)
        {
            if ($usedClass == $thisClassName || $timesImported < 1)
                continue;

            $useStatement = sprintf("%suse %s;\n", $useStatement, ltrim($usedClass, "\\"));
        }

        return $useStatement;
    }
}