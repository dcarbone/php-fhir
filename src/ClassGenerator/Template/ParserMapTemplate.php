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

/**
 * Class ParserMapTemplate
 * @package PHPFHIR\ClassGenerator\Template
 */
class ParserMapTemplate extends AbstractTemplate
{
    /** @var string */
    private static $_template = <<<STRING
<?php namespace %s;

%s

use PHPFHIR\\Parser\\ParserMapInterface;

class PHPFHIRParserMap implements ParserMapInterface
{
    /** @var array */
    private \$_elementClassMap = %s;

    /** @var array */
    private \$_structureMap = %s;

    /**
     * @var string \$elementName
     * @return array|null
     */
    public function getElementStructure(\$elementName)
    {
        if (isset(\$this->_structureMap[\$elementName]))
            return \$this->_structureMap[\$elementName];

        return null;
    }

    /**
     * @var string \$name
     * @return array|null
     */
    public function getElementClass(\$name)
    {
        if (isset(\$this->_elementClassMap[\$name]))
            return \$this->_elementClassMap[\$name];

        return null;
    }
}
STRING;

    /** @var array */
    private $_elementClassMap = array();
    /** @var array */
    private $_classMap = array();

    /** @var array */
    private $_extendsMap = array();
    /** @var array */
    private $_setterMap = array();
    /** @var array */
    private $_propertyMap = array();

    /** @var string */
    private $_outputPath;
    /** @var string */
    private $_outputNamespace;

    /** @var string */
    private $_classPath;
    /** @var string */
    private $_className;

    /**
     * Constructor
     *
     * @param string $outputPath
     * @param string $outputNamespace
     */
    public function __construct($outputPath, $outputNamespace)
    {
        $this->_outputPath = rtrim($outputPath, "\\/");
        $this->_outputNamespace = $outputNamespace;

        $this->_classPath = sprintf('%s/%s/PHPFHIRParserMap.php', $this->_outputPath, $this->_outputNamespace);
        $this->_className = sprintf('%s\\PHPFHIRParserMap', $this->_outputNamespace);
    }

    /**
     * @param string $elementName
     * @param ClassTemplate $classTemplate
     */
    public function addElementClass($elementName, ClassTemplate $classTemplate)
    {
        if (isset($this->_elementClassMap[$elementName]))
        {
            throw new \RuntimeException(sprintf(
                    'Element with name %s is already defined with class %s.  New input: %s',
                    $elementName,
                    $this->_elementClassMap[$elementName],
                    $classTemplate->getClassName()
                )
            );
        }

        $className = $classTemplate->getClassName();
        $classNamespace = $classTemplate->getNamespace();
        $fullClassName = sprintf('\\%s\\%s', $classNamespace, $className);
        $extendedClass = $classTemplate->getExtends();
        $isPrimitive = false !== stripos($className, 'primitive');
        $isList = false !== stripos($className, 'list');

        $this->_elementClassMap[$elementName] = $fullClassName;
        $this->_classMap[$className] = $this->_elementClassMap[$elementName];
        $this->_propertyMap[$elementName] = array();

        if ($isPrimitive || $isList)
        {
            $this->_setterMap[$elementName]['value'] = 'setValue';
            $this->_propertyMap[$elementName]['value'] = 'primitive';
        }
        else
        {
            if ($extendedClass)
                $this->_extendsMap[$fullClassName] = $extendedClass;

            $this->_setterMap[$elementName] = array();

            foreach($classTemplate->getMethods() as $method)
            {
                if ($method instanceof SetterMethodTemplate)
                {
                    /** @var \PHPFHIR\ClassGenerator\Template\ParameterTemplate $parameter */
                    foreach($method->getParameters() as $parameter)
                    {
                        $this->_setterMap[$elementName][$parameter->getName()] = $method->getName();
                    }
                }
            }

            /** @var \PHPFHIR\ClassGenerator\Template\PropertyTemplate $property */
            foreach($classTemplate->getProperties() as $property)
            {
                $this->_propertyMap[$elementName][$property->getName()] = $property->getTypes();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function compileTemplate()
    {
        $structure = array();

        foreach($this->_elementClassMap as $elementName=>$fullClassName)
        {
            $structure[$elementName] = array(
                'class' => $fullClassName,
                'properties' => array()
            );

            $this->buildPropertiesStructure($elementName, $fullClassName, $structure[$elementName]['properties']);
        }

        var_dump($structure);

        return sprintf(
            self::$_template,
            $this->_outputNamespace,
            CopyrightUtils::getBasePHPFHIRCopyrightComment(),
            var_export($this->_elementClassMap, true),
            var_export($structure, true)
        );
    }

    /**
     * @return bool
     */
    public function writeToFile()
    {
        return (bool)file_put_contents(
            $this->_classPath,
            $this->compileTemplate()
        );
    }

    protected function buildPropertiesStructure($elementName, $fullClassName, array &$_map = array())
    {
        if (isset($this->_extendsMap[$fullClassName]))
            $this->getExtendedClassProperties($fullClassName, $_map);

        foreach($this->_propertyMap[$elementName] as $paramName=>$paramDef)
        {
            if ('extension' === $paramName)
            {
                $_map['extension'] = array(
                    'class' => $this->_classMap['FHIRExtension'],
                    'setter' => 'addExtension'
                );
            }
            else
            {
                if ($paramDef === 'primitive')
                {

                }
                else
                {
                    foreach($paramDef as $paramElementName=>$paramType)
                    {
                        if (false === strpos($paramType, '-primitive')
                            && false === strpos($paramType, '-list'))
                        {
//                        if (isset($this->_elementClassMap))
//
//                            var_dump(
//                                $paramType,
//                                isset($this->_elementClassMap[$paramType]),
//                                isset($this->_classMap[$paramType]));
//                        echo str_repeat('-', 50);
//                        echo "\n";
//                        $_map[$paramElementName] = array(
////                                'class' =>
//                        );
                        }
                        else if (isset($this->_classMap[$paramType]))
                        {
                            $_map[$paramElementName] = array(
                                'class' => $this->_classMap[$paramType],
                                'setter' => 'setValue'
                            );
                        }
                        else if (isset($this->_elementClassMap[$paramType]))
                        {
                            $_map[$paramElementName] = array(
                                'class' => $this->_elementClassMap[$paramType],
                                'setter' => $this->_setterMap[$elementName][$paramElementName]
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $fullClassName
     * @param array $_map
     */
    protected function getExtendedClassProperties($fullClassName, array &$_map)
    {
        $extendedClass = $this->_extendsMap[$fullClassName];
        $fullExtendedClassName = $this->_classMap[$extendedClass];
        $extendedElementName = array_search($fullExtendedClassName, $this->_elementClassMap);

        if (-1 === $extendedElementName)
        {
            throw new \RuntimeException(sprintf(
                'Unable to find element name for class "%s".  This indicates corrupted internal element -> class mapping, and should be reported as a bug.',
                $fullExtendedClassName
            ));
        }

        $this->buildPropertiesStructure($extendedElementName, $fullExtendedClassName, $_map);

        if (isset($this->_extendsMap[$fullExtendedClassName]))
            $this->getExtendedClassProperties($fullExtendedClassName, $_map);
    }
}