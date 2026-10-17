<?php declare(strict_types=1);

/*
 * Copyright 2024-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);
$pathResolver = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_PATH_RESOLVER);
$valueMatcher = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALUE_MATCHER);

$imports->addCoreFileImports(
    $typeInterface,
    $validationRuleInterface,
    $pathResolver,
    $valueMatcher,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $validationRuleInterface; ?>

{
    public const NAME = 'slice_occurs';
    public const DESCRIPTION = 'Asserts that the number of values matching each declared slice is within its bounds.';

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
        $items = null === $value ? [] : (is_array($value) ? $value : [$value]);
        $errs = [];
        foreach ($constraint as $slice) {
            $count = 0;
            foreach ($items as $item) {
                if (<?php echo $valueMatcher; ?>::matches($item, $slice['match'])) {
                    $count++;
                }
            }
            if (isset($slice['min']) && $count < $slice['min']) {
                $errs[] = sprintf('slice "%s" requires at least %d, found %d', $slice['name'], $slice['min'], $count);
            }
            if (isset($slice['max']) && $count > $slice['max']) {
                $errs[] = sprintf('slice "%s" allows at most %d, found %d', $slice['name'], $slice['max'], $count);
            }
        }
        if ([] === $errs) {
            return null;
        }
        return sprintf('Field "%s" on type "%s": %s', $field, $type->_getFHIRTypeName(), implode('; ', $errs));
    }
}

<?php return ob_get_clean();
