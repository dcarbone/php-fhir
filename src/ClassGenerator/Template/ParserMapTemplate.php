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
    public function addEntry(ClassTemplate $classTemplate)
    {
        $fhirElementName = $classTemplate->getElementName();

        $extendedMapEntry = $classTemplate->getExtendedElementMapEntry();
        $this->_bigDumbMap[$fhirElementName] = array(
            'fullClassName' =>  $classTemplate->compileFullyQualifiedClassName(true),
            'extendedElementName' => $extendedMapEntry ? $extendedMapEntry->getFHIRElementName() : null,
            'properties' => array()
        );

        foreach($classTemplate->getMethods() as $method)
        {
            if ($method instanceof SetterMethodTemplate)
            {
                foreach($method->getParameters() as $parameter)
                {
                    $property = $parameter->getProperty();
                    $this->_bigDumbMap[$fhirElementName]['properties'][$property->getName()] = array(
                        'setter' => $method->getName(),
                        'element' => $property->getFHIRElementType(),
                        'type' => $property->getPhpType()
                    );
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
            include TEMPLATE_DIR.'/parser_map_template.php',
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
     * @param array $_entry
     */
    protected function getExtendedProperties($elementName, array &$_entry)
    {
        if (isset($this->_bigDumbMap[$elementName]['extendedElementName']))
        {
            $extendedElement = $this->_bigDumbMap[$elementName]['extendedElementName'];

            if (!isset($this->_bigDumbMap[$extendedElement]))
            {
                throw new \RuntimeException(sprintf(
                    'Unable to find element named %s.  This indicates corrupted internal element mapping and should be reported as a bug',
                    $extendedElement
                ));
            }

            // Extended class properties go first, yo.
            $_entry = $this->_bigDumbMap[$extendedElement]['properties'] + $_entry;

            $this->getExtendedProperties($extendedElement, $_entry);
        }
    }
}