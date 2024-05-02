<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Class <?php echo PHPFHIR_CLASSNAME_CONSTANTS; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>

{
    // FHIR source
    public const SOURCE_VERSION = '<?php echo CopyrightUtils::getFHIRVersion(false); ?>';
    public const SOURCE_GENERATION_DATE = '<?php echo CopyrightUtils::getFHIRGenerationDate(); ?>';

    // PHPFHIR
    public const CODE_GENERATION_DATE = '<?php echo CopyrightUtils::getStandardDate(); ?>';

    // Common
    public const JSON_FIELD_RESOURCE_TYPE = 'resourceType';
    public const JSON_FIELD_FHIR_COMMENTS = 'fhir_comments';
    public const STRING_TRUE = 'true';
    public const STRING_FALSE = 'false';

    // Date and time formats
    public const DATE_FORMAT_YEAR = 'Y';
    public const DATE_FORMAT_YEAR_MONTH = 'Y-m';
    public const DATE_FORMAT_YEAR_MONTH_DAY = 'Y-m-d';
    public const DATE_FORMAT_YEAR_MONTH_DAY_TIME = 'Y-m-d\\TH:i:s\\.uP';
    public const DATE_FORMAT_INSTANT = 'Y-m-d\\TH:i:s\\.uP';
    public const TIME_FORMAT = 'H:i:s';

    public const UNLIMITED = -1;

    // Validation
    public const <?php echo PHPFHIR_VALIDATION_ENUM_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_ENUM; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MIN_LENGTH_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MIN_LENGTH; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MAX_LENGTH_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MAX_LENGTH; ?>';
    public const <?php echo PHPFHIR_VALIDATION_PATTERN_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_PATTERN; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MIN_OCCURS_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MIN_OCCURS; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MAX_OCCURS_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MAX_OCCURS; ?>';

    // Type names
<?php foreach($types->getNameSortedIterator() as $type) : ?>
    public const <?php echo $type->getTypeNameConst(false); ?> = '<?php echo $type->getFHIRName(); ?>';
<?php endforeach;?>

    // Type classes
<?php foreach($types->getNameSortedIterator() as $type) : ?>
    public const <?php echo $type->getClassNameConst(false); ?> = '<?php echo str_replace('\\', '\\\\', $type->getFullyQualifiedClassName(true)); ?>';
<?php endforeach;
echo "}\n";
return ob_get_clean();