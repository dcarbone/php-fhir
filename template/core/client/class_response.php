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
$imports = $coreFile->getImports();

$respHeaderClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE_HEADERS);
$methodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);

$imports->addCoreFileImports(
    $respHeaderClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo PHPFHIR_CLIENT_CLASSNAME_RESPONSE; ?>

{
    /**
     * HTTP request method.
     *
     * @var <?php echo $methodEnum->getFullyQualifiedName(true); ?>

     */
    public <?php echo $methodEnum; ?> $method;

    /**
     * Request URL.
     *
     * @var string
     */
    public string $url;

    /**
     * HTTP response status code.
     *
     * @var int
     */
    public int $code;

    /**
     * HTTP response headers.
     *
     * @var <?php echo $respHeaderClass->getFullyQualifiedName(true); ?>

     */
    public <?php echo $respHeaderClass; ?> $headers;

    /**
     * HTTP response body.
     *
     * @var string
     */
    public string $resp;

    /**
     * Client error.
     *
     * @var string
     */
    public string $err;

    /**
     * Client error number.
     *
     * @var int
     */
    public int $errno;

    public function __construct(<?php echo $methodEnum ?> $method, string $url)
    {
        $this->method = $method;
        $this->url = $url;
    }

    /**
     * Return the HTTP request method used.
     *
     * @return null|<?php echo $methodEnum->getFullyQualifiedName(true); ?>

     */
    public function getMethod(): null|<?php echo $methodEnum; ?>

    {
        return $this->method ?? null;
    }

    /**
     * Return the full URL used.
     *
     * @return null|string
     */
    public function getURL(): null|string
    {
        return $this->url ?? null;
    }

    /**
     * Return the HTTP response code seen.
     *
     * @return null|int
     */
    public function getCode(): null|int
    {
        return $this->code ?? null;
    }

    /**
     * Return the HTTP response headers seen.
     *
     * @return null|<?php echo $respHeaderClass->getFullyQualifiedName(true); ?>

     */
    public function getHeaders(): null|<?php echo $respHeaderClass; ?>

    {
        return $this->headers ?? null;
    }

    /**
     * Return the full response seen, if there was one.
     *
     * @return null|string
     */
    public function getResp(): null|string
    {
        return $this->resp ?? null;
    }

    /**
     * Client error message, if there was one.
     *
     * @return null|string
     */
    public function getErr(): null|string
    {
        return $this->err ?? null;
    }

    /**
     * Client error code, if there was one.
     *
     * @return null|int
     */
    public function getErrno(): null|int
    {
        return $this->errno ?? null;
    }
}
<?php return ob_get_clean();