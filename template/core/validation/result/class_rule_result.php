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
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$validatorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR);
$ruleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $validatorClass,
    $ruleInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $ruleInterface; ?>

{
    /**
     * The name of the rule that produced this response.
     *
     * @var string
     */
    public string $rule;

    /**
     * Error message from rule.  If not defined, rule is assumed to have passed.
     *
     * @var string
     */
    public string $error;

    /**
     * If set to true, halts execution of further rules.  The outcome of this rule is used as the final validation
     * pass / fail state for the set.
     *
     * @var bool
     */
    public bool $halt;

    public function __construct(string $rule,
                                string $fhirType,
                                string $field,
                                mixed $constraint,
                                string $error,
                                bool $halt = false) {
        $this->rule = $rule;
        $this->fhirType = fhirType;
        $this->field = $field;
        $this->constraint = $constraint;
        $this->error = $error;
        $this->halt = $halt;
    }

    /**
     * Returns true if the rule passed
     *
     * @return bool
     */
    public function ok(): bool
    {
        return unset($this->error) || '' === $this->error;
    }

    /**
     * Override default JSON serialization to prevent really angry constraint values from messin' stuff up.
     *
     * @return \stdClass
     */
    public function jsonSerialize(): \stdClass
    {
        $out = new \stdClass();
        foreach($this as $k => $v) {
            if ('constraint' !== $k) {
                $out->{$k} = $v;
            } else if (is_scalar($v)) {
                $out->constraint = $v;
            } else if (is_object($v)) {
                $out->constraint = ($v instanceof \JsonSerializable) ? $v : get_class($v);
            } else if (is_array($v)) {
                $unsafeTypes = [];
                foreach($v as $vv) {
                    if (is_scalar($vv) {
                        continue;
                    }
                    $unsafeType[] = match(true) {
                        is_object($vv) => get_class($vv),
                        is_array($vv) => sprintf('Array[%d]', count($vv)),
                        default => gettype($vv),
                    }
                }
                $out->constraint = match($unsafeTypes) {
                    [] => $v,
                    default => $unsafeTypes,
                };
            } else {
                $out->constraint = gettype($v);
            }
        }
    }
}
<?php return ob_get_clean();
