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
        return $this[$objectName]['className'];
    }

    /**
     * @param string $objectName
     * @return null|string
     */
    public function getClassUseStatementForObject($objectName)
    {
        return sprintf('%s\\%s', $this[$objectName]['rootNS'], $this[$objectName]['className']);
    }
}