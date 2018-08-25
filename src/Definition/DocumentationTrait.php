<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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
 * Trait DocumentationTrait
 * @package DCarbone\PHPFHIR
 */
trait DocumentationTrait
{
    /** @var string */
    private $documentation;

    /**
     * @return string
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    /**
     * @param $documentation
     * @return static
     */
    public function setDocumentation($documentation)
    {
        $this->documentation = $documentation;
        return $this;
    }
}