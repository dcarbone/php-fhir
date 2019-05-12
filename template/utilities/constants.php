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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
const FHIR_SOURCE_VERSION = '<?php echo CopyrightUtils::getFHIRVersion(); ?>';
const CODE_GENERATION_DATE = '<?php echo CopyrightUtils::getStandardDate(); ?>';

<?php foreach($types->getSortedIterator() as $type) :
    $constName = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $type->getFHIRName())); ?>
const TYPE_NAME_<?php echo $constName; ?> = '<?php echo $type->getFHIRName(); ?>';
const TYPE_CLASS_<?php echo $constName; ?> = '<?php echo $type->getFullyQualifiedClassName(true); ?>';
<?php endforeach;
return ob_get_clean();