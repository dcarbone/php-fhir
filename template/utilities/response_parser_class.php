<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\FileUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n"; ?>

/**
* Class <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; if ('' !== $namespace) : ?>

    * @package \<?php echo $namespace; ?>
<?php endif; ?>

*/
class <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>

{
    /** @var \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> $config */
    private $config;

    /**
     * <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> Constructor
     * @param \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> $config
     */
    public function __construct(<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> $config = null)
    {
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>;
        }
        $this->config = $config;
    }

    /**
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>
     */
    public function getConfig()
    {
        return $this->config;
    }
}
<?php return ob_get_clean();