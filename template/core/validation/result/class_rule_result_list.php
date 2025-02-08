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
$ruleResultClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_RULE_RESULT);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $ruleResultClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements \Countable, \JsonSerializable
{
    /** @var <?php echo $ruleResultClass->getFullyQualifiedName(true); ?>[] */
    protected array $_results = [];

    protected int $_okCount = 0;
    protected int $_nokCount = 0;

    /**
     * Returns true if there are no errored results.
     *
     * @return bool
     */
    public function ok(): bool
    {
        return 0 === $this->_nokCount;
    }

    /**
     * Returns the number of OK results.
     *
     * @return int
     */
    public function okCount(): int
    {
        return $this->_okCount;
    }

    /**
     * Returns the number of errored results
     *
     * @return int
     */
    public function erroredCount(): int
    {
        return $this->_nokCount;
    }

    public function count(): int
    {
        return count($this->_results);
    }

    /**
     * @return <?php echo $ruleResultClass->getFullyQualifiedName(true); ?>[]
     */
    public function getResultIterator(): iterable
    {
        if ([] === $this->_results) {
            return new \EmptyIterator();
        }
        return new \ArrayIterator($this->_results);
    }

    /**
     * Append a single result to this list at the specified path.
     *
     * @param string $path
     * @param <?php echo $ruleResultClass->getFullyQualifiedName(true); ?> $result
     */
    public function addResult(string $path, <?php echo $ruleResultClass; ?> $result): void
    {
        $this->_results[$path] = $result;
        if ($result->ok()) {
            $this->_okCount++;
        } else {
            $this->_nokCount++;
        }
    }

    /**
     * Append another result list's results to this list under the provided prefix.
     *
     * @param string $pathPrefix
     * @param <?php echo $coreFile->getFullyQualifiedName(true); ?> $other
     */
    public function appendResults(string $pathPrefix, <?php echo $coreFile; ?> $other): void
    {
        $this->_okCount += $other->_okCount;
        $this->_nokCount += $other->_nokCount;
        foreach ($other->_results as $subPath => $res) {
            $this->_results["{$pathPrefix}.{$subPath}"] = $res;
        }
    }

    public function jsonSerialize(): array
    {
        return $this->_results;
    }
}
<?php return ob_get_clean();
