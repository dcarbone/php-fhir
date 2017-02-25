<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template;

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
 * TODO: Restructure template system so documentation is not implemented where applicable.
 *
 * Class AbstractTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
abstract class AbstractTemplate
{
    /** @var array */
    protected $documentation;

    /**
     * @return array
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    /**
     * @param string|array|null $documentation
     */
    public function setDocumentation($documentation)
    {
        if (null !== $documentation)
        {
            if (is_string($documentation))
                $documentation = array($documentation);

            if (is_array($documentation))
                $this->documentation = $documentation;
            else
                throw new \InvalidArgumentException('Documentation expected to be array, string, or null.');
        }
    }

    /**
     * @return string
     */
    abstract public function compileTemplate();

    /**
     * @see compileTemplate
     * @return string By default, returns output of compileTemplate
     */
    public function __toString()
    {
        return $this->compileTemplate();
    }

    /**
     * @param int $spaces
     * @return string
     */
    protected function getDocBlockDocumentationFragment($spaces = 5)
    {
        $output = '';
        $spaces = str_repeat(' ', $spaces);
        if (isset($this->documentation))
        {
            foreach($this->documentation as $doc)
            {
                $output = sprintf("%s%s* %s\n", $output, $spaces, $doc);
            }
        }
        return $output;
    }
}