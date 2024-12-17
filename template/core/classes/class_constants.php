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

/** @var \DCarbone\PHPFHIR\Config $config */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


final class <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>

{
    // PHPFHIR
    public const CODE_GENERATION_DATE = '<?php echo $config->getStandardDate(); ?>';

    // Common
    public const JSON_FIELD_RESOURCE_TYPE = 'resourceType';
    public const JSON_FIELD_FHIR_COMMENTS = 'fhir_comments';
    public const STRING_TRUE = 'true';
    public const STRING_FALSE = 'false';

    // Date and time formats
    public const DATE_FORMAT_YEAR = '<?php echo PHPFHIR_DATE_FORMAT_YEAR; ?>';
    public const DATE_FORMAT_YEAR_MONTH = '<?php echo PHPFHIR_DATE_FORMAT_YEAR_MONTH; ?>';
    public const DATE_FORMAT_YEAR_MONTH_DAY = '<?php echo PHPFHIR_DATE_FORMAT_YEAR_MONTH_DAY; ?>';
    public const DATE_FORMAT_YEAR_MONTH_DAY_TIME = '<?php echo PHPFHIR_DATE_FORMAT_YEAR_MONTH_DAY_TIME; ?>';
    public const DATE_FORMAT_INSTANT = '<?php echo PHPFHIR_DATE_FORMAT_INSTANT; ?>';
    public const TIME_FORMAT = '<?php echo PHPFHIR_TIME_FORMAT; ?>';

    public const UNLIMITED = -1;

    // Validation
    public const <?php echo PHPFHIR_VALIDATION_ENUM_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_ENUM; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MIN_LENGTH_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MIN_LENGTH; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MAX_LENGTH_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MAX_LENGTH; ?>';
    public const <?php echo PHPFHIR_VALIDATION_PATTERN_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_PATTERN; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MIN_OCCURS_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MIN_OCCURS; ?>';
    public const <?php echo PHPFHIR_VALIDATION_MAX_OCCURS_NAME; ?> = '<?php echo PHPFHIR_VALIDATION_MAX_OCCURS; ?>';
}
<?php
return ob_get_clean();
