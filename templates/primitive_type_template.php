<?php

return <<<STRING
<?php namespace %s;

%s

abstract class AbstractPHPFHIRPrimitiveType
{
    /** @var mixed */
    public \$value = null;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return \$this->value;
    }

    /**
     * @param mixed \$value
     */
    public function setValue(\$value)
    {
        \$this->value = \$value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)\$this->value;
    }
}
STRING;
