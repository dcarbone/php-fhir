<?php namespace PHPFHIR;

use DCarbone\AbstractCollectionPlus;

/**
 * Class XSDMap
 * @package PHPFHIR
 */
class XSDMap extends AbstractCollectionPlus
{
    /**
     * @param string $objectName
     * @return string|null
     */
    public function getClassNameForObject($objectName)
    {
        if (isset($this[$objectName]))
            return $this[$objectName]['className'];

        return null;
    }

    /**
     * @param string $objectName
     * @return null|string
     */
    public function getClassUseStatementForObject($objectName)
    {
        if (isset($this[$objectName]))
            return sprintf('%s\\%s', $this[$objectName]['rootNS'], $this[$objectName]['className']);

        return null;
    }
}