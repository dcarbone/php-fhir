<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$formatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT);
$sortEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_SORT_DIRECTION);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


class <?php echo PHPFHIR_CLIENT_CLASSNAME_REQUEST; ?>

{
    /** @var string */
    public string $method;

    /** @var string */
    public string $path;

    /** @var int */
    public int $count;
    /** @var string */
    public string $since;
    /** @var string */
    public string $at;

    /** @var bool */
    public bool $parseHeaders;

    /** @var <?php echo $formatEnum->getFullyQualifiedName(true); ?> */
    public <?php echo PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT; ?> $format;

    /** @var <?php echo $sortEnum->getFullyQualifiedName(true); ?> */
    public <?php echo PHPFHIR_CLIENT_ENUM_SORT_DIRECTION; ?> $sort;

    /**
     * Extra query parameters.
     *
     * @var array
     */
    public array $queryParams;

    /**
     * Extra options.  Possible values depends on what client you are using.  If using the base API client, these
     * must be valid PHP curl options.
     *
     * @var array
     */
    public array $options;
}
<?php return ob_get_clean();