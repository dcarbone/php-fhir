<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config $config */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Interface <?php echo PHPFHIR_INTERFACE_RESPONSE_PARSER; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_RESPONSE_PARSER; ?>

{
    /**
     * Must attempt to parse the provided input into FHIR objects.
     *
     * @param null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function parse(null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>;
}
