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

use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;

/**
 * Class ParserMapTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class ParserMapTemplate extends AbstractTemplate
{
    /** @var string */
    private static $_template = <<<STRING
<?php namespace %s;

%s

use DCarbone\\PHPFHIR\\Parser\\ParserMapInterface;

class PHPFHIRParserMap implements ParserMapInterface
{
    /** @var array */
    private \$_bigDumbMap = %s;

    /**
     * @param mixed \$offset
     * @return bool
     */
    public function offsetExists(\$offset)
    {
        return isset(\$this->_bigDumbMap[\$offset]);
    }

    /**
     * @param mixed \$offset
     * @return mixed
     */
    public function offsetGet(\$offset)
    {
        if (isset(\$this->_bigDumbMap[\$offset]))
            return \$this->_bigDumbMap[\$offset];

        trigger_error(sprintf(
            'Offset %%s does not exist in the FHIR element map, this could either mean a malformed response or a bug in the generator.',
            \$offset
        ));

        return null;
    }

    /**
     * @param mixed \$offset
     * @param mixed \$value
     */
    public function offsetSet(\$offset, \$value)
    {
        throw new \\BadMethodCallException('Not allowed to set values on the FHIR parser element map');
    }

    /**
     * @param mixed \$offset
     */
    public function offsetUnset(\$offset)
    {
        throw new \\BadMethodCallException('Not allowed to unset values in this FHIR parser element map');
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current(\$this->_bigDumbMap);
    }

    /**
     * @return string
     */
    public function key()
    {
        return key(\$this->_bigDumbMap);
    }

    public function next()
    {
        next(\$this->_bigDumbMap);
    }

    public function rewind()
    {
        reset(\$this->_bigDumbMap);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return key(\$this->_bigDumbMap) !== null;
    }
}
STRING;

    /** @var array */
    private $_bigDumbMap = array();

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
     * TODO: Make this not terrible.
     *
     * @param ClassTemplate $classTemplate
     */
    public function addClass(ClassTemplate $classTemplate)
    {
        $fhirElementName = $classTemplate->getElementName();
        $className = $classTemplate->getClassName();

        $isPrimitive = false !== stripos($className, 'primitive');
        $isList = false !== stripos($className, 'list');

        $this->_bigDumbMap[$fhirElementName] = array(
            'className' => $className,
            'fullClassName' => $classTemplate->compileFullyQualifiedClassName(true),
            'extendedClass' => $classTemplate->getExtendedClassName(),
            'extendedElement' => $classTemplate->getExtendedElementName(),
            'primitive' => $isPrimitive,
            'list' => $isList,
            'properties' => array()
        );

        if ($isList || $isPrimitive)
        {
            $this->_bigDumbMap[$fhirElementName]['properties']['value'] = array(
                'setter' => 'setValue',
                'type' => 'primitive'
            );
        }
        else
        {
            foreach($classTemplate->getMethods() as $method)
            {
                if ($method instanceof SetterMethodTemplate)
                {
                    foreach($method->getParameters() as $parameter)
                    {
                        $types = $parameter->getPropertyTypes();
                        $types = reset($types);
                        $this->_bigDumbMap[$fhirElementName]['properties'][$parameter->getName()] = array(
                            'setter' => $method->getName(),
                            'type' => $types['elementName']
                        );
                    }
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function compileTemplate()
    {
        $this->addExtendedClassProperties();

        return sprintf(
            self::$_template,
            $this->_outputNamespace,
            CopyrightUtils::getBasePHPFHIRCopyrightComment(),
            var_export($this->_bigDumbMap, true)
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

    protected function addExtendedClassProperties()
    {
        $elementNames = array_keys($this->_bigDumbMap);
        foreach($elementNames as $elementName)
        {
            $this->getExtendedProperties($elementName, $this->_bigDumbMap[$elementName]['properties']);
        }
    }

    /**
     * @param string $elementName
     * @param array $_properties
     */
    protected function getExtendedProperties($elementName, array &$_properties)
    {
        // This indicates we are at a primitive element.
        if (!isset($this->_bigDumbMap[$elementName]['extendedClass']))
            return;

        $extendedElement = $this->_bigDumbMap[$elementName]['extendedElement'];

        if ('primitive' === $extendedElement)
            return;

        if (!isset($this->_bigDumbMap[$extendedElement]))
        {
            throw new \RuntimeException(sprintf(
                'Unable to find element named %s.  This indicates corrupted internal element mapping and should be reported as a bug',
                $extendedElement
            ));
        }

        // Extended class properties go first, yo.
        $_properties = $this->_bigDumbMap[$extendedElement]['properties'] + $_properties;

        $this->getExtendedProperties($extendedElement, $_properties);
    }
}