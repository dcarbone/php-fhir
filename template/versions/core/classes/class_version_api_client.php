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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

$config = $version->getConfig();
$types = $version->getDefinition()->getTypes();
$namespace = $version->getFullyQualifiedName(false);
$bundleType = $types->getBundleType();

$idType = $types->getTypeByName('id');
$idPrimitiveType = $types->getTypeByName('id-primitive');

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_API_CLIENT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_ENUM_API_FORMAT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_API_CLIENT_REQUEST); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_EXCEPTION_API_CURL_ERROR); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_EXCEPTION_API_UNEXPECTED_RESPONSE_CODE); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_ENUM_API_SORT); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_RESPONSE_PARSER); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_TYPE); ?>;
use <?php echo $idType->getFullyQualifiedClassName(false); ?>;

class <?php echo PHPFHIR_CLASSNAME_VERSION_API_CLIENT; ?>

{
    private const _STATUS_OK = 200;

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
     * Queries for one or more resources of a given type, returning the raw response fromm the server.
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param string|<?php echo PHPFHIR_ENUM_VERSION_TYPE; ?> $resourceType
     * @param null|string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID
     * @param null|int $count
     * @param null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort
     * @param null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format
     * @param null|bool $parseheaders
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     * @throws \Exception
     */
    public function readRaw(string|<?php echo PHPFHIR_ENUM_VERSION_TYPE; ?> $resourceType,
                            null|string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID = null,
                            null|int $count = null,
                            null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort = null,
                            null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = null,
                            null|bool $parseheaders = null): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>

    {
        if (!is_string($resourceType)) {
            $resourceType = $resourceType->value;
        }

        $req = new <?php echo PHPFHIR_CLASSNAME_API_CLIENT_REQUEST; ?>();
        $req->method = 'GET';
        $req->path = "/{$resourceType}";
        if (null !== $resourceID) {
            $req->path .= "/{$resourceID}";
        }
        if (null !== $count) {
            $req->count = $count;
        }
        if (null !== $sort) {
            $req->sort = $sort;
        }
        if (null !== $format) {
            $req->format = $format;
        }
        if (null !== $parseheaders) {
            $req->parseHeaders = $parseheaders;
        }

        return $this->_client->exec($req);
    }

    /**
     * Queries for one or more resources of a given type, returning the unserialized response from the server.
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param string|<?php echo PHPFHIR_ENUM_VERSION_TYPE; ?> $resourceType
     * @param null|string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID
     * @param null|int $count
     * @param null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort
     * @param null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format
     * @param null|bool $parseheaders
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function read(string|<?php echo PHPFHIR_ENUM_VERSION_TYPE; ?> $resourceType,
                         null|string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID = null,
                         null|int $count = null,
                         null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort = null,
                         null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = null,
                         null|bool $parseheaders = null): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $rc = $this->readRaw($resourceType, $resourceID, $count, $sort, $format, $parseheaders);
        $this->_requireOK($rc);
        return <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>::parse($this->_version, $rc->resp);
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?> $rc
     * @throws <?php echo $config->getFullyQualifiedName(true, PHPFHIR_EXCEPTION_API_CURL_ERROR); ?>

     * @throws <?php echo $config->getFullyQualifiedName(true, PHPFHIR_EXCEPTION_API_UNEXPECTED_RESPONSE_CODE); ?>

     */
    protected function _requireOK(<?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?> $rc): void
    {
        if (isset($rc->err)) {
            throw new <?php echo PHPFHIR_EXCEPTION_API_CURL_ERROR; ?>($rc);
        }
        if (!isset($rc->code) || self::_STATUS_OK !== $rc->code) {
            throw new <?php echo PHPFHIR_EXCEPTION_API_UNEXPECTED_RESPONSE_CODE; ?>($rc, self::_STATUS_OK);
        }
    }
<?php foreach($types->getChildrenOf('Resource') as $rsc) :
    $typeKind = $rsc->getKind();
    if ($rsc->isRootType() || $typeKind->isContainer($version) || $typeKind->isOneOf(TypeKindEnum::RESOURCE_COMPONENT)) { continue; } ?>

    /**
     * Query for one or more <?php echo $rsc->getFHIRName(); ?> resources.
     *
     * @param null|string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID
     * @param null|int $count
     * @param null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort
     * @param null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format
     * @param null|bool $parseheaders
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     * @throws \Exception
     */
    public function read<?php echo $rsc->getFHIRName(); ?>Raw(null|string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID = null, null|int $count = null, null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort = null, null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = null, null|bool $parseheaders = null): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>

    {
        return $this->readRaw(<?php echo PHPFHIR_ENUM_VERSION_TYPE; ?>::<?php echo $rsc->getConstName(false); ?>, $resourceID, $count, $sort, $format, $parseheaders);
    }
<?php endforeach; ?>

}
<?php return ob_get_clean();