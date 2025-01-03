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

$respHeaderClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CLIENT_RESPONSE_HEADERS);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


class <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

{
    /**
     * HTTP request method.
     *
     * @var string
     */
    public string $method;

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
    public <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE_HEADERS; ?> $headers;

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

    public function getMethod(): null|string
    {
        return $this->method ?? null;
    }

    public function getURL(): null|string
    {
        return $this->url ?? null;
    }

    public function getCode(): null|int
    {
        return $this->code ?? null;
    }

    public function getHeaders(): null|<?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE_HEADERS; ?>

    {
        return $this->headers ?? null;
    }

    public function getResp(): null|string
    {
        return $this->resp ?? null;
    }

    public function getErr(): null|string
    {
        return $this->err ?? null;
    }

    public function getErrno(): null|int
    {
        return $this->errno ?? null;
    }
}
<?php return ob_get_clean();