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

use DCarbone\PHPFHIR\Enum\ValidationRuleNameEnum;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);
$pathResolver = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_PATH_RESOLVER);

$imports->addCoreFileImports(
    $typeInterface,
    $validationRuleInterface,
    $pathResolver,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $validationRuleInterface; ?>

{
    public const NAME = '<?php echo ValidationRuleNameEnum::CODE_BINDING->value; ?>';
    public const DESCRIPTION = 'Asserts that a code, Coding or CodeableConcept holds a code from a required set of [system, code] pairs.';

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
        if (null === $value || [] === $value) {
            return null;
        }
        $items = is_array($value) ? $value : [$value];
        foreach ($items as $item) {
            if (!self::_allowed($item, $constraint)) {
                return sprintf('Field "%s" on type "%s" does not contain a code from the required value set', $field, $type->_getFHIRTypeName());
            }
        }
        return null;
    }

    private static function _allowed(mixed $item, array $codes): bool
    {
        if (!$item instanceof <?php echo $typeInterface; ?>) {
            return self::_codeAllowed($codes, null, <?php echo $pathResolver; ?>::unwrap($item));
        }
        // codings, codeable concepts and bare codes are told apart by the getters they expose.
        $code = is_callable([$item, 'getCode']) ? $item->getCode() : null;
        if (null !== $code) {
            return self::_codeAllowed($codes, <?php echo $pathResolver; ?>::unwrap(is_callable([$item, 'getSystem']) ? $item->getSystem() : null), <?php echo $pathResolver; ?>::unwrap($code));
        }
        $codings = is_callable([$item, 'getCoding']) ? $item->getCoding() : null;
        if (is_array($codings)) {
            foreach ($codings as $coding) {
                if (self::_allowed($coding, $codes)) {
                    return true;
                }
            }
            return false;
        }
        return self::_codeAllowed($codes, null, <?php echo $pathResolver; ?>::unwrap($item));
    }

    private static function _codeAllowed(array $codes, mixed $system, mixed $code): bool
    {
        if (!is_string($code)) {
            return false;
        }
        foreach ($codes as [$allowedSystem, $allowedCode]) {
            if ($code === $allowedCode && (!is_string($system) || null === $allowedSystem || $system === $allowedSystem)) {
                return true;
            }
        }
        return false;
    }
}

<?php return ob_get_clean();
