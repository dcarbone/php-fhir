<?php namespace PHPFHIRGenerated\FHIRElement\FHIRQuantity;

use PHPFHIRGenerated\FHIRElement\FHIRQuantity;

class FHIRMoney extends FHIRQuantity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $_fhirElementName = 'Money';

    /**
     * @return string
     */
    public function get_fhirElementName()
    {
        return $this->_fhirElementName;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get_fhirElementName();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        return $json;
    }

    /**
     * @param boolean $returnSXE
     * @param \SimpleXMLElement $sxe
     * @return string|\SimpleXMLElement
     */
    public function xmlSerialize($returnSXE = false, $sxe = null)
    {
        if (null === $sxe) $sxe = new \SimpleXMLElement('<Money xmlns="http://hl7.org/fhir"></Money>');
        parent::xmlSerialize(true, $sxe);
        if ($returnSXE) return $sxe;
        return $sxe->saveXML();
    }


}