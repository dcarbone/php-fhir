<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_VALIDATION_RULE);

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

class <?php echo $coreFile->getEntityName(); ?> implements <?php echo $validationRuleInterface->getEntityName(); ?>

{
    public const NAME = 'min_occurs';
    public const DESCRIPTION = 'Asserts that a given collection field is of a specific minimum length';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDescription(): string
    {
        return self::DESCRIPTION;
    }

    public function assert(<?php echo $typeInterface->getEntityName(); ?> $type, string $field, mixed $constraint, mixed $value): null|string
    {
        if (0 >= $constraint || (1 === $constraint && $value instanceof <?php echo $typeInterface->getEntityName(); ?>)) {
            return null;
        }
        if (null === $value || [] === $value) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, but it is empty', $field, $type->_getFHIRTypeName(), $constraint);
        }
        $len = count($value);
        if ($constraint > $len) {
            return sprintf('Field "%s" on type "%s" must have at least %d elements, %d seen.', $field, $type->_getFHIRTypeName(), $constraint, $len);
        }
        return null;
    }
}
<?php return ob_get_clean();
