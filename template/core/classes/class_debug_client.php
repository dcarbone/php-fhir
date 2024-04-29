<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n"; ?>
/**
 * This client is intended for debug purposes only, and is not intended to be used in a production environment.
 *
 * Its API is subject to change at any time.
 *
 * Class <?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT; ?>

{
    private const _BASE_CURL_OPTS = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => 0,
        CURLOPT_USERAGENT => 'php-fhir debug client (build: <?php echo CopyrightUtils::getFHIRGenerationDate(); ?>; fhir version: <?php echo $config->getVersion()->getName(); ?>)',
    ];

    /** @var string */
    private string $_baseUrl;
    /** @var array */
    private array $_curlOpts;

    /**
     * <?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT; ?> Constructor
     *
     * @param string $baseUrl URL of FHIR server to query
     * @param array $curlOpts Base curl options array
     */
    public function __construct(string $baseUrl, array $curlOpts = [], )
    {
        $this->_baseUrl = $baseUrl;
        $this->_curlOpts = $curlOpts;
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo '' !== $namespace ? sprintf('\\%s', $namespace) : ''; ?>\<?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT_RESPONSE; ?>

     */
    public function get(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT_RESPONSE; ?>
    {
        $url = sprintf('%s%s?%s', $this->_baseUrl, $path, http_build_query($queryParams, '', '&',  PHP_QUERY_RFC3986));

        $ch = curl_init($url);
        curl_setopt_array(
            $ch,
            self::_BASE_CURL_OPTS + $this->_curlOpts + $curlOpts
        );

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $rc = new <?php echo PHPFHIR_CLASSNAME_DEBUG_CLIENT_RESPONSE; ?>();
        $rc->url = $url;
        $rc->code = $code;
        $rc->resp = $resp;
        $rc->err = $err;

        return $rc;
    }

    /**
     * Execute a read operation for a particular resource.
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param string|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_ENUM_TYPE; ?> $resource
     * @param string $id
     * @param string|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_ENUM_API_FORMAT; ?> $format
     * @param null|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> $parser
     * @return null|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>

     * @throws \Exception
     */
    public function readOne(string|<?php echo PHPFHIR_ENUM_TYPE; ?> $resource, string $id, string|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = <?php echo PHPFHIR_ENUM_API_FORMAT; ?>::JSON, null|<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> $parser = null): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if (!is_string($resource)) {
            $resource = $resource->value;
        }
        if (!is_string($format)) {
            $format = $format->value;
        }

        $rc = $this->get(sprintf('/%s/%s', $resource, $id), ['_format' => $format]);

        if ('' !== $rc->err) {
            throw new \Exception(sprintf('Error executing "%s": %s', $rc->url, $rc->err));
        }
        if (200 !== $rc->code) {
            throw new \Exception(sprintf('Error executing "%s": Expected 200 OK, saw %d', $rc->url, $rc->code));
        }

        if (null === $parser) {
            $parser = new <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>(new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>());
        }

        return $parser->parse($rc->resp);
    }

    /**
     * Queries for the first available of a given resource
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param string|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_ENUM_TYPE; ?> $resource
     * @param string|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_ENUM_API_FORMAT; ?> $format
     * @param null|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> $parser
     * @return null|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>

     * @throws \Exception
     */
    public function readFirst(string|<?php echo PHPFHIR_ENUM_TYPE; ?> $resource, string|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = <?php echo PHPFHIR_ENUM_API_FORMAT; ?>::JSON, null|<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> $parser = null): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if (!is_string($resource)) {
            $resource = $resource->value;
        }
        if (!is_string($format)) {
            $format = $format->value;
        }

        $rc = $this->get(sprintf('/%s', $resource), ['_format' => $format, '_count' => '1']);

        if ('' !== $rc->err) {
            throw new \Exception(sprintf('Error executing "%s": %s', $rc->url, $rc->err));
        }
        if (200 !== $rc->code) {
            throw new \Exception(sprintf('Error executing "%s": Expected 200 OK, saw %d', $rc->url, $rc->code));
        }

        if (null === $parser) {
            $parser = new <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>(new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>());
        }

        return $parser->parse($rc->resp);
    }
}
<?php return ob_get_clean();