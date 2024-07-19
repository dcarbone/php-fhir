<?php declare(strict_types=1);

/*
 * Copyright 2022-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Version\VersionCopyright;

/** @var \DCarbone\PHPFHIR\Config $config */

$rootNS = $config->getFullyQualifiedName(false);

ob_start();
echo "<?php declare(strict_types=1);\n\n";

if ('' !== $rootNS) :
    echo "namespace {$rootNS};\n\n";
endif;

echo VersionCopyright::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Trait <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; if ('' !== $rootNS) : ?>

 * @package \<?php echo $rootNS; ?>
<?php endif; ?>

 */
trait <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; ?>

{
    /** @var int */
    private int $_valuesAdded = 0;
    /** @var int */
    private int $_valuesRemoved = 0;

    /**
     * Used to track the setting of a given value, taking into consideration whether a value is being overwritten
     *
     * @param mixed $original
     * @param mixed $new
     * @return void
     */
    protected function _trackValueSet(mixed $original, mixed $new): void {
        if ($original === $new) {
            return;
        }
        if (null === $original && null !== $new) {
            $this->_valuesAdded++;
        } elseif (null !== $original && null === $new) {
            $this->_valuesRemoved++;
        } else {
            $this->_valuesAdded++;
            $this->_valuesRemoved++;
        }
    }

    /**
     * Used to record a value being added to a collection
     *
     * @return void
     */
    protected function _trackValueAdded(): void
    {
        $this->_valuesAdded++;
    }

    /**
     * Used to record $n items being removed from a collection
     *
     * @param int $n
     * @return void
     */
    protected function _trackValuesRemoved(int $n): void
    {
        $this->_valuesRemoved += $n;
    }

    /**
     * Returns true if there are valued fields on the contained type
     *
     * @return bool
     */
    public function _isValued(): bool
    {
        return $this->_valuesAdded > $this->_valuesRemoved;
    }

    /**
     * Returns the number of times any field has been set on this type
     *
     * @return int
     */
    public function _getValueAddedCount(): int
    {
        return $this->_valuesAdded;
    }

    /**
     * Returns the number of times any field has been removed on this type
     *
     * @return int
     */
    public function _getValueRemovedCount(): int
    {
        return $this->_valuesRemoved;
    }

    /**
     * Returns the current number of values defined on this type
     *
     * @return int
     */
    public function _getValueSetCount(): int
    {
        return $this->_valuesAdded - $this->_valuesRemoved;
    }
}
<?php return ob_get_clean();