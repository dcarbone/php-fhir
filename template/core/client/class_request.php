<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$clientClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CLIENT);
$formatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT);
$httpMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);
$sortEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_SORT_DIRECTION);
$responseClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE);

$imports = $coreFile->getimports();

$imports->addCoreFileImports(
    $clientClass,
    $formatEnum,
    $httpMethodEnum,
    $sortEnum,
    $responseClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

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

    /**
     * The serialization type to request from the server.  Typically this is 'json' or 'xml'.
     *
     * @var string
     */
    public string $format;

    /** @var string */
    public string $sort;

    /**
     * Extra query parameters.
     *
     * @var array
     */
    public array $queryParams;

    /**
     * If true, headers from the response must be returned and defined in the response object.
     *
     * @see <?php echo $responseClass->getFullyQualifiedName(true); ?>::$headers
     *
     * @var bool
     */
    public bool $parseResponseHeaders;

    /**
     * Extra client options.  Possible entries will vary depending on what client implementation you are using.
     *
     * If using the provided client (@see <?php echo $clientClass->getFullyQualifiedName(true); ?> class),
     * these must be valid PHP curl options.
     *
     * @var array
     */
    public array $options;

    public function __construct(<?php echo $httpMethodEnum->getEntityName(); ?> $method,
                                string $path,
                                null|int $count = null,
                                null|string $since = null,
                                null|string $at = null,
                                null|<?php echo $formatEnum->getEntityName(); ?> $format = null,
                                null|<?php echo $sortEnum->getEntityName(); ?> $sort = null,
                                null|array $queryParams = null,
                                null|bool $parseResponseHeaders = null,
                                null|array $options = null)
    {
        $this->method = $method->value;
        $this->path = $path;
        if (null !== $count) {
            $this->count = $count;
        }
        if (null !== $since) {
            $this->since = $since;
        }
        if (null !== $at) {
            $this->at = $at;
        }
        if (null !== $format) {
            $this->format = $format->value;
        }
        if (null !== $sort) {
            $this->sort = $format->value;
        }
        if (null !== $queryParams) {
            $this->queryParams = $queryParams;
        }
        if (null !== $parseResponseHeaders) {
            $this->parseResponseHeaders = $parseResponseHeaders;
        }
        if (null !== $options) {
            $this->options = $options;
        }
    }
}
<?php return ob_get_clean();