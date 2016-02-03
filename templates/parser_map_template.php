<?php

return <<<STRING
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
