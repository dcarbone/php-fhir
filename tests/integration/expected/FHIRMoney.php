<?php namespace PHPFHIRGenerated;

use PHPFHIRGenerated\FHIRElement\FHIRQuantity;
use PHPFHIRGenerated\JsonSerializable;

class FHIRMoney extends FHIRQuantity implements JsonSerializable
{
    /**
     * The value of the measured amount. The value includes an implicit precision in the presentation of the value.
     * @var \PHPFHIRGenerated\FHIRElement\FHIRDecimal
     */
    public $value = null;

    /**
     * How the value should be understood and represented - whether the actual value is greater or less than the stated value due to measurement issues. E.g. if the comparator is "<" , then the real value is < stated value.
     * @var \PHPFHIRGenerated\FHIRElement\FHIRQuantityCompararator
     */
    public $comparator = null;

    /**
     * A human-readable form of the units.
     * @var \PHPFHIRGenerated\FHIRElement\FHIRString
     */
    public $units = null;

    /**
     * The identification of the system that provides the coded form of the unit.
     * @var \PHPFHIRGenerated\FHIRElement\FHIRUri
     */
    public $system = null;

    /**
     * A computer processable form of the units in some unit representation system.
     * @var \PHPFHIRGenerated\FHIRElement\FHIRCode
     */
    public $code = null;

    /**
     * @var string
     */
    public $id = null;

    /**
     * @var string
     */
    private $_fhirElementName = 'Money';

    /**
     * The value of the measured amount. The value includes an implicit precision in the presentation of the value.
     * @return \PHPFHIRGenerated\FHIRElement\FHIRDecimal
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * The value of the measured amount. The value includes an implicit precision in the presentation of the value.
     * @param \PHPFHIRGenerated\FHIRElement\FHIRDecimal $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * How the value should be understood and represented - whether the actual value is greater or less than the stated value due to measurement issues. E.g. if the comparator is "<" , then the real value is < stated value.
     * @return \PHPFHIRGenerated\FHIRElement\FHIRQuantityCompararator
     */
    public function getComparator()
    {
        return $this->comparator;
    }

    /**
     * How the value should be understood and represented - whether the actual value is greater or less than the stated value due to measurement issues. E.g. if the comparator is "<" , then the real value is < stated value.
     * @param \PHPFHIRGenerated\FHIRElement\FHIRQuantityCompararator $comparator
     * @return $this
     */
    public function setComparator($comparator)
    {
        $this->comparator = $comparator;
        return $this;
    }

    /**
     * A human-readable form of the units.
     * @return \PHPFHIRGenerated\FHIRElement\FHIRString
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * A human-readable form of the units.
     * @param \PHPFHIRGenerated\FHIRElement\FHIRString $units
     * @return $this
     */
    public function setUnits($units)
    {
        $this->units = $units;
        return $this;
    }

    /**
     * The identification of the system that provides the coded form of the unit.
     * @return \PHPFHIRGenerated\FHIRElement\FHIRUri
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * The identification of the system that provides the coded form of the unit.
     * @param \PHPFHIRGenerated\FHIRElement\FHIRUri $system
     * @return $this
     */
    public function setSystem($system)
    {
        $this->system = $system;
        return $this;
    }

    /**
     * A computer processable form of the units in some unit representation system.
     * @return \PHPFHIRGenerated\FHIRElement\FHIRCode
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * A computer processable form of the units in some unit representation system.
     * @param \PHPFHIRGenerated\FHIRElement\FHIRCode $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

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
        return (string)$this->getValue();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        if (null !== $this->value) $json['value'] = $this->value->jsonSerialize();
        if (null !== $this->comparator) $json['comparator'] = $this->comparator->jsonSerialize();
        if (null !== $this->units) $json['units'] = $this->units->jsonSerialize();
        if (null !== $this->system) $json['system'] = $this->system->jsonSerialize();
        if (null !== $this->code) $json['code'] = $this->code->jsonSerialize();
        if (null !== $this->id) $json['id'] = $this->id;
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
        if (null !== $this->value) $this->value->xmlSerialize(true, $sxe->addChild('value'));
        if (null !== $this->comparator) $this->comparator->xmlSerialize(true, $sxe->addChild('comparator'));
        if (null !== $this->units) $this->units->xmlSerialize(true, $sxe->addChild('units'));
        if (null !== $this->system) $this->system->xmlSerialize(true, $sxe->addChild('system'));
        if (null !== $this->code) $this->code->xmlSerialize(true, $sxe->addChild('code'));
        if (null !== $this->id) {
            $idElement = $sxe->addChild('id');
            $idElement->addAttribute('value', (string)$this->id);
        }
        if ($returnSXE) return $sxe;
        return $sxe->saveXML();
    }


}
