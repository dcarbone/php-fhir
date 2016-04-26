<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR;

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

use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\SetterMethodTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;

/**
 * Class ParserMapTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR
 */
class ParserMapTemplate extends AbstractPHPFHIRClassTemplate
{
    /** @var array */
    private $_bigDumbMap = array();

    /**
     * Constructor
     *
     * @param string $outputPath
     * @param string $outputNamespace
     */
    public function __construct($outputPath, $outputNamespace)
    {
        parent::__construct($outputPath, $outputNamespace, 'PHPFHIRParserMap');
    }

    /**
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
                /** @var \DCarbone\PHPFHIR\ClassGenerator\Template\Parameter\PropertyParameterTemplate $parameter */
                foreach($method->getParameters() as $parameter)
                {
                    $property = $parameter->getProperty();
                    $this->_bigDumbMap[$fhirElementName]['properties'][$property->getName()] = array(
                        'setter' => $method->getName(),
                        'element' => $property->getFHIRElementType(),
                        'type' => $property->getPHPType()
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
            include PHPFHIR_TEMPLATE_DIR.'/parser_map_template.php',
            $this->outputNamespace,
            CopyrightUtils::getBasePHPFHIRCopyrightComment(),
            var_export($this->_bigDumbMap, true)
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