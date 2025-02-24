<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);

$imports->addCoreFileImports(
    $typeInterface,
    $primitiveTypeInterface,
    $validationRuleInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $validationRuleInterface; ?>

{
    public const NAME = 'value_one_of';
    public const DESCRIPTION = 'Asserts that a given value is within the expected list of values.';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDescription(): string
    {
        return self::DESCRIPTION;
    }

    public function assert(<?php echo $typeInterface; ?> $type, string $field, mixed $constraint, mixed $value): null|string
    {
        if (null === $value || [] === $constraint) {
            return null;
        }
        if ($value instanceof <?php echo $primitiveTypeInterface; ?>) {
            $value = (string)$value;
        }
        if (in_array($value, $constraint, true)) {
            return null;
        }
        return sprintf(
            'Field "%s" on type "%s" value "%s" not one of [%s]',
            $field,
            $type->_getFHIRTypeName(),
            var_export($value, true),
            implode(
                ', ',
                array_map(
                    fn($v) => var_export($v, true),
                    $constraint
                )
            )
        );
    }
}
<?php return ob_get_clean();
