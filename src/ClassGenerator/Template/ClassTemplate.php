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
    protected $className;

    /** @var string */
    protected $pseudonym;

    /** @var string */
    protected $namespace;

    /** @var array */
    protected $uses = array();

    /** @var string */
    protected $extends;

    /** @var PropertyTemplate[] */
    protected $properties = array();

    /** @var AbstractMethodTemplate[] */
    protected $methods = array();

    /**
     * Constructor
     *
     * @param string $className
     * @param string $namespace
     * @param string $pseudonym
     */
    public function __construct($className, $namespace, $pseudonym)
    {
        if (NameUtils::isValidClassName($className))
            $this->className = $className;
        else
            throw new \InvalidArgumentException('Class Name "'.$className.'" is not valid.');

        if (NameUtils::isValidNSName($namespace))
            $this->namespace = $namespace;
        else
            throw new \InvalidArgumentException('Namespace "' . $namespace . '" is not valid.');

        if (NameUtils::isValidClassName($pseudonym))
            $this->pseudonym = $pseudonym;
        else
            throw new \InvalidArgumentException('Class Pseudonym "'.$pseudonym.'" is not valid.');
    }

    /**
     * @return string
     */
    public function getUseStatement()
    {
        return sprintf('%s\\%s;', $this->namespace, $this->className);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * @param string $use
     */
    public function addUse($use)
    {
        $this->uses[] = $use;
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
    public function getPseudonym()
    {
        return $this->pseudonym;
    }

    /**
     * @param string $extends
     */
    public function setExtends($extends)
    {
        $this->extends = $extends;
    }

    /**
     * @return string
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param PropertyTemplate $property
     */
    public function addProperty(PropertyTemplate $property)
    {
        $this->properties[$property->getName()] = $property;
    }

    /**
     * @param AbstractMethodTemplate $method
     */
    public function addMethod(AbstractMethodTemplate $method)
    {
        $this->methods[$method->getName()] = $method;
    }

    // TODO: Possibly omit __toString use and write directly to file.

    /**
     * @param string $outputPath
     * @return bool
     */
    public function writeToFile($outputPath)
    {
        return (bool)file_put_contents(
            $this->compileFullOutputPath($outputPath),
            $this->compileClassDefinition()
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
    public function compileClassDefinition()
    {
        $ns = $this->getNamespace();
        if ('' === $ns)
            $output = "<?php\n\n";
        else
            $output = sprintf("<?php namespace %s;\n\n", $ns);

        $output = sprintf("%s%s\n\n%s", $output, CopyrightUtils::getFullPHPFHIRCopyrightComment(), $this->_compileUseStatements());

        if ("\n\n" !== substr($output, -2))
            $output = sprintf("%s\n", $output);

        if (isset($this->documentation) && count($this->documentation) > 0)
            $output = sprintf("%s/**\n%s */\n", $output, self::_getDocumentationOutput(1));

        if ($this->extends)
            $output = sprintf("%sclass %s extends %s\n", $output, $this->getClassName(), $this->getExtends());
        else
            $output = sprintf("%sclass %s\n", $output, $this->getClassName());

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
    public function __toString()
    {
        return $this->compileClassDefinition();
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