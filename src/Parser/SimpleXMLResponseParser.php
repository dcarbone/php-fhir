<?php namespace PHPFHIR\Parser;

/**
 * Class SimpleXMLResponseParser
 * @package PHPFHIR\Parser
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