<?php namespace DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR;

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

use DCarbone\PHPFHIR\ClassGenerator\Template\AbstractTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\FileUtils;
use DCarbone\PHPFHIR\ClassGenerator\Utilities\NameUtils;

/**
 * Class AbstractPHPFHIRClassTemplate
 * @package DCarbone\PHPFHIR\ClassGenerator\Template\PHPFHIR
 */
abstract class AbstractPHPFHIRClassTemplate extends AbstractTemplate
{
    /** @var string */
    protected $class;

    /** @var string */
    protected $outputPath;
    /** @var string */
    protected $outputNamespace;

    /** @var string */
    protected $classPath;

    /** @var string */
    protected $className;

    /**
     * @param string $outputPath
     * @param string $outputNamespace
     * @param string $class
     */
    public function __construct($outputPath, $outputNamespace, $class)
    {
        $this->outputPath = rtrim($outputPath, "\\/");
        $this->outputNamespace = $outputNamespace;

        if (NameUtils::isValidClassName($class))
        {
            $this->class = $class;
        }
        else
        {
            throw new \RuntimeException(
                sprintf(
                    '%s::__construct - Specified invalid class name %s.',
                    get_called_class(),
                    $class
                )
            );
        }

        $this->classPath = sprintf(
            '%s/%s/%s.php',
            $this->outputPath,
            FileUtils::buildDirPathFromNS($this->outputNamespace),
            $class
        );

        $this->className = sprintf('%s\\%s', $this->outputNamespace, $class);
    }

    /**
     * @return string
     */
    public function getClassPath()
    {
        return $this->classPath;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return bool
     */
    public function writeToFile()
    {
        return (bool)file_put_contents(
            $this->classPath,
            $this->compileTemplate()
        );
    }
}