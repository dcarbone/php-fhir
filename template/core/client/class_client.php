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

$clientInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_INTERFACE_CLIENT);
$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);
$requestClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_REQUEST);
$responseClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE);
$httpMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);
$formatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT);
$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);
$responseHeaderClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE_HEADERS);

$imports->addCoreFileImports(
    $clientInterface,
    $clientConfigClass,
    $requestClass,
    $responseClass,
    $httpMethodEnum,
    $formatEnum,
    $fhirVersion,
    $responseHeaderClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Class <?php echo $coreFile; ?>

 *
 * Basic implementation of the <?php echo $clientInterface; ?> interface.
 */
class <?php echo $coreFile; ?> implements <?php echo $clientInterface; ?>

{
    private const _PARAM_FORMAT = '_format';
    private const _PARAM_SORT = '_sort';
    private const _PARAM_COUNT = '_count';

    private const _BASE_CURL_OPTS = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'php-fhir client (build: <?php echo $config->getStandardDate(); ?>;)',
    ];

    protected <?php echo $clientConfigClass; ?> $_config;

    /**
     * <?php echo $coreFile; ?> Constructor
     *
     * @param string|<?php echo $clientConfigClass->getFullyQualifiedName(true); ?> $config Fully qualified address of FHIR server, or configuration object.
     */
    public function __construct(string|<?php echo $clientConfigClass; ?> $config)
    {
        if (is_string($config)) {
            $config = new <?php echo $clientConfigClass; ?>(address: $config);
        }
        $this->_config = $config;
    }

    public function getConfig(): <?php echo $clientConfigClass; ?>

    {
        return $this->_config;
    }

    /**
     * @param <?php echo $requestClass->getFullyQualifiedName(true); ?> $request
     * @return <?php echo $responseClass->getFullyQualifiedName(true); ?>

     */
    public function exec(<?php echo $requestClass; ?> $request): <?php echo $responseClass; ?>

    {
        $queryParams = array_merge($this->_config->getDefaultQueryParams(), $request->queryParams ?? []);
        $format = $request->format ?? $this->_config->getDefaultFormat();
        $parseResponseHeaders = match(true) {
            isset($request->parseResponseHeaders) => $request->parseResponseHeaders,
            default => $this->_config->getParseResponseHeaders(),
        };
        $acceptVersion = match(true) {
            isset($request->version) => $request->version,
            isset($request->resource) => $request->resource->_getFHIRVersion(),
            default => null,
        };
        $contentTypeVersion = match(true) {
            isset($request->resource) => $request->resource->_getFHIRVersion(),
            default => null,
        };

        $queryParams[self::_PARAM_FORMAT] = $format->value;
        if (isset($request->sort)) {
            $queryParams[self::_PARAM_SORT] = $request->sort;
        }
        if (isset($request->count)) {
            $queryParams[self::_PARAM_COUNT] = $request->count;
        }

        $url = "{$this->_config->getAddress()}{$request->path}?" . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $rc = new <?php echo $responseClass; ?>($request->method, $url, $format);

        $curlOpts = self::_BASE_CURL_OPTS + array_merge($this->_config->getCurlOpts(), $request->options ?? []);

        if ($parseResponseHeaders) {
            $rc->headers = new <?php echo $responseHeaderClass; ?>();
            $curlOpts[CURLOPT_HEADER] = 1;
            $curlOpts[CURLOPT_HEADERFUNCTION] = function($ch, string $line) use (&$rc): int {
                    return $rc->headers->addLine($line);
                };
        }

        if (!isset($curlOpts[CURLOPT_HTTPHEADER])) {
            $curlOpts[CURLOPT_HTTPHEADER] = [];
        }

        if (<?php echo $httpMethodEnum; ?>::GET !== $request->method) {
            $curlOpts[CURLOPT_CUSTOMREQUEST] = $request->method->value;
            $curlOpts[CURLOPT_HTTPHEADER][] = "X-HTTP-Method-Override: {$request->method->value}";
        }

        if (null === $acceptVersion) {
            $curlOpts[CURLOPT_HTTPHEADER][] = "Accept: application/{$format->value}+json, application/json+{$format->value}";
        } else if ($acceptVersion->getFHIRVersionInteger() < <?php echo $fhirVersion; ?>::STU3_MIN_VERSION_INTEGER) {
            $curlOpts[CURLOPT_HTTPHEADER][] = "Accept: application/{$format->value}+fhir; fhirVersion={$acceptVersion->getFHIRShortVersion()}";
        } else {
            $curlOpts[CURLOPT_HTTPHEADER][] = "Accept: application/fhir+{$format->value}; fhirVersion={$acceptVersion->getFHIRShortVersion()}";
        }
        if (null !== $contentTypeVersion) {
            if ($contentTypeVersion->getFHIRVersionInteger() < <?php echo $fhirVersion; ?>::STU3_MIN_VERSION_INTEGER) {
                $curlOpts[CURLOPT_HTTPHEADER][] = "Content-Type: application/{$format->value}+fhir; fhirVersion={$contentTypeVersion->getFHIRShortVersion()}";
            } else {
                $curlOpts[CURLOPT_HTTPHEADER][] = "Content-Type: application/fhir+{$format->value}; fhirVersion={$contentTypeVersion->getFHIRShortVersion()}";
            }
        }

        $ch = curl_init($url);
        if (!curl_setopt_array($ch, $curlOpts)) {
            throw new \DomainException(sprintf(
                'curl_setopt_array returned false for "%s" with options: %s',
                $url,
                var_export($curlOpts, true),
            ));
        }

        $resp = curl_exec($ch);
        $rc->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $rc->err = curl_error($ch);
        $rc->errno = curl_errno($ch);
        curl_close($ch);

        if (0 === $rc->errno) {
            if ($parseResponseHeaders) {
                $rc->resp = substr($resp, $rc->headers->getLength());
            } else {
                $rc->resp = $resp;
            }
        }

        return $rc;
    }
}
<?php return ob_get_clean();
