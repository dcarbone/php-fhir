<?php namespace PHPFHIR\OAuth\Providers;

/*
 * Copyright 2016 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use League\OAuth2\Client\Provider\IdentityProvider;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Class PHPLeagueFHIR
 * @package PHPFHIR\OAuth\Providers
 */
class PHPLeagueFHIR extends IdentityProvider
{
    /** @var string */
    private $_authURL;
    /** @var string */
    private $_tokenURL;

    /**
     * Constructor
     *
     * @param array $options
     * @param string $authURL
     * @param string $tokenURL
     */
    public function __construct(array $options, $authURL, $tokenURL)
    {
        parent::__construct($options);
        $this->_authURL = $authURL;
        $this->_tokenURL = $tokenURL;
    }

    /**
     * @return string
     */
    public function urlAuthorize()
    {
        return $this->_authURL;
    }

    /**
     * @return string
     */
    public function urlAccessToken()
    {
        return $this->_tokenURL;
    }

    /**
     * @param AccessToken $token
     * @return null
     */
    public function urlUserDetails(AccessToken $token)
    {
        return null;
    }

    /**
     * @param $response
     * @param AccessToken $token
     * @return null
     */
    public function userDetails($response, AccessToken $token)
    {
        return null;
    }
}