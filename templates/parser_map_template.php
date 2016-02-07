<?php

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

return <<<STRING
<?php namespace %s;

%s

class PHPFHIRParserMap implements \ArrayAccess, \Iterator
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
