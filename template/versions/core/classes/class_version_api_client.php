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

/** @var \DCarbone\PHPFHIR\Version $version */

$config = $version->getConfig();
$namespace = $version->getFullyQualifiedName(false);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_API_CLIENT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_ENUM_API_FORMAT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_ENUM_API_RESOURCE_LIST); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_API_CLIENT_REQUEST); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_ENUM_API_SORT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_RESPONSE_PARSER); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_TYPE); ?>;

class <?php echo PHPFHIR_CLASSNAME_VERSION_API_CLIENT; ?>

{
    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_API_CLIENT); ?> */
    protected <?php echo PHPFHIR_INTERFACE_API_CLIENT; ?> $_client;

    /** @var <?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION); ?> */
    protected <?php echo PHPFHIR_CLASSNAME_VERSION; ?> $_version;

    /**
     * <?php echo PHPFHIR_CLASSNAME_VERSION_API_CLIENT; ?> Constructor
     *
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_API_CLIENT); ?> $client
     * @param <?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION); ?> $version
     */
    public function __construct(<?php echo PHPFHIR_INTERFACE_API_CLIENT; ?> $client, <?php echo PHPFHIR_CLASSNAME_VERSION; ?> $version)
    {
        $this->_client = $client;
        $this->_version = $version;
    }

    /**
     * Queries for one or more resources of a given type
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param string|<?php echo PHPFHIR_ENUM_VERSION_TYPE; ?> $resourceType
     * @param int $count
     * @param null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort
     * @param null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function read(string|<?php echo PHPFHIR_ENUM_VERSION_TYPE; ?> $resourceType,
                         int $count = 1,
                         null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort = null,
                         null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = null): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if (!is_string($resourceType)) {
            $resourceType = $resourceType->value;
        }

        $req = new <?php echo PHPFHIR_CLASSNAME_API_CLIENT_REQUEST; ?>();
        $req->method = 'GET';
        $req->path = sprintf('/%s', $resourceType);
        $req->count = $count;
        if (null !== $sort) {
            $req->sort = $sort;
        }
        if (null !== $format) {
            $req->format = $format;
        }

        $rc = $this->_client->exec($req);

        if ('' !== $rc->err) {
            throw new \Exception(sprintf('Error executing "%s": %s', $rc->url, $rc->err));
        }
        if (200 !== $rc->code) {
            throw new \Exception(sprintf('Error executing "%s": Expected 200 OK, saw %d', $rc->url, $rc->code));
        }

        return <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>::parse($this->_version, $rc->resp);
    }
}
<?php return ob_get_clean();