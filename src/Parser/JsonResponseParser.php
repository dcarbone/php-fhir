<?php namespace PHPFHIR\Parser;
use DCarbone\Helpers\JsonErrorHelper;

/**
 * Class JsonResponseParser
 * @package PHPFHIR\Parser
 */
class JsonResponseParser extends AbstractResponseParser
{
    /**
     * @param string $input
     * @return object
     */
    public function parse($input)
    {
        if (!is_string($input))
        {
            throw new \InvalidArgumentException(sprintf(
                '%s::parse - Argument 1 expected to be string, %s seen.',
                get_class($this),
                gettype($input)
            ));
        }

        $decoded = json_decode($input, true);

        $lastError = json_last_error();
        if ($lastError !== JSON_ERROR_NONE)
        {
            throw new \DomainException(sprintf(
                '%s::parse - Error encountered while parsing JSON: %s',
                get_class($this),
                JsonErrorHelper::invoke(true, $lastError)
            ));
        }

        return $this->_parseObject($decoded, $decoded['resourceType']);
    }

    /**
     * @param array $jsonEntry
     * @param string $fhirElementName
     * @return object
     */
    private function _parseObject($jsonEntry, $fhirElementName)
    {
        $map = $this->tryGetMapEntry($fhirElementName);

        $fullClassName = $map['fullClassName'];
        $properties = $map['properties'];

        $object = new $fullClassName;

        // This indicates we are at a primitive value...
        if (is_scalar($jsonEntry))
        {
            if (($map['primitive'] || $map['list']))
            {
                $object->setValue($jsonEntry);
            }
            else
            {
                $propertyMap = $properties['value'];
                $setter = $propertyMap['setter'];
                $object->$setter($this->createPrimitive($jsonEntry, $propertyMap['type']));
            }
        }
        else
        {
            foreach($jsonEntry as $k=>$v)
            {
                if ($k === 'resourceType')
                    continue;

                if (!isset($properties[$k]))
                {
                    $this->triggerPropertyNotFoundError($fhirElementName, $k);
                    continue;
                }

                $propertyMap = $properties[$k];
                $setter = $propertyMap['setter'];
                $type = $propertyMap['type'];

                if (is_array($v))
                {
                    $firstKey = key($v);

                    if (is_string($firstKey))
                    {
                        if (isset($v['resourceType']))
                            $object->$setter($this->_parseObject($v, $v['resourceType']));
                        else
                            $object->$setter($this->_parseObject($v, $type));
                    }
                    else
                    {
                        foreach($v as $child)
                        {
                            $object->$setter($this->_parseObject($child, $type));
                        }
                    }
                }
                else
                {
                    $object->$setter($this->_parseObject($v, $type));
                }
            }
        }

        return $object;
    }


}