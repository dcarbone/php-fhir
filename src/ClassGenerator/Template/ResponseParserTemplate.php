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

use DCarbone\PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;

/**
 * Class ResponseParserTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template
 */
class ResponseParserTemplate extends AbstractTemplate
{
    /** @var string */
    private $_outputPath;
    /** @var string */
    private $_outputNamespace;

    /** @var string */
    private $_classPath;

    /** @var string */
    private $_className;

    /**
     * Constructor
     *
     * @param string $outputPath
     * @param string $outputNamespace
     */
    public function __construct($outputPath, $outputNamespace)
    {
        $this->_outputPath = rtrim($outputPath, "\\/");
        $this->_outputNamespace = $outputNamespace;

        $this->_classPath = sprintf('%s/%s/PHPFHIRResponseParser.php', $this->_outputPath, $this->_outputNamespace);
        $this->_className = sprintf('%s\\PHPFHIRResponseParser', $this->_outputNamespace);
    }

    /**
     * @return string
     */
    public function compileTemplate()
    {
        return sprintf(
            include PHPFHIR_TEMPLATE_DIR.'/response_parser_template.php',
            $this->_outputNamespace,
            CopyrightUtils::getBasePHPFHIRCopyrightComment()
        );
    }

    /**
     * @return bool
     */
    public function writeToFile()
    {
        return (bool)file_put_contents(
            $this->_classPath,
            $this->compileTemplate()
        );
    }

    /**
     * @return string
     */
    public function getClassPath()
    {
        return $this->_classPath;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->_className;
    }
}