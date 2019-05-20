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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

ob_start();

echo "<?php\n\n";

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
// FHIR source
const FHIR_SOURCE_VERSION = '<?php echo CopyrightUtils::getFHIRVersion(); ?>';
const FHIR_SOURCE_GENERATION_DATE = '<?php echo CopyrightUtils::getFHIRGenerationDate(); ?>';

// PHPFHIR
const FHIR_CODE_GENERATION_DATE = '<?php echo CopyrightUtils::getStandardDate(); ?>';

// Common
const FHIR_JSON_FIELD_RESOURCE_TYPE = 'resourceType';

// Type names and classes
<?php foreach($types->getSortedIterator() as $type) : ?>
const <?php echo $type->getTypeNameConst(); ?> = '<?php echo $type->getFHIRName(); ?>';
const <?php echo $type->getClassNameConst(); ?> = '<?php echo str_replace('\\', '\\\\', $type->getFullyQualifiedClassName(true)); ?>';
<?php endforeach;
return ob_get_clean();