<?php namespace DCarbone\PHPFHIR\Parser;

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

/**
 * Class SimpleXMLResponseParser
 * @package DCarbone\PHPFHIR\Parser
 */
class SimpleXMLResponseParser extends AbstractResponseParser
{
    /**
     * @param string $response
     * @param int $sxeArgs
     * @return object
     */
    public function parse($response, $sxeArgs = null)
    {
        if (!is_string($response))
            throw $this->createNonStringArgumentException($response);

        if (!is_int($sxeArgs))
            $sxeArgs = LIBXML_COMPACT | LIBXML_NSCLEAN;

        libxml_use_internal_errors(true);
        $sxe = new \SimpleXMLElement($response, $sxeArgs);
        $error = libxml_get_last_error();
        libxml_use_internal_errors(false);

        if ($sxe instanceof \SimpleXMLElement)
            return $this->_parseNode($sxe, $sxe->getName());

        throw new \RuntimeException(sprintf(
            'Unable to parse response: "%s"',
            ($error ? $error->message : 'Unknown Error')
        ));
    }

    /**
     * @param \SimpleXMLElement $element
     * @param string $fhirElementName
     * @return mixed
     */
    private function _parseNode(\SimpleXMLElement $element, $fhirElementName)
    {
        $map = $this->tryGetMapEntry($fhirElementName);

        $fullClassName = $map['fullClassName'];
        $properties = $map['properties'];

        $object = new $fullClassName;

        if (isset($element['value']))
        {
            $propertyMap = $properties['value'];
            $setter = $propertyMap['setter'];
            $object->$setter($this->createPrimitive((string)$element['value'], $propertyMap['type']));
        }
        else
        {
            /** @var \SimpleXMLElement $childElement */
            foreach($element->children() as $childElement)
            {
                $childName = $childElement->getName();
                if (!isset($properties[$childName]))
                {
                    $this->triggerPropertyNotFoundError($fhirElementName, $childName);
                    continue;
                }

                $propertyMap = $properties[$childName];
                $setter = $propertyMap['setter'];
                $type = $propertyMap['type'];

                $object->$setter($this->_parseNode($childElement, $type));
            }
        }

        return $object;
    }
}