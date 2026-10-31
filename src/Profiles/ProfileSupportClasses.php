<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Fully qualified names, without leading slash, of the core classes generated profile classes rely on.  Any left
 * null is referred to by its bare class name.
 */
class ProfileSupportClasses
{
    private readonly null|string $_valueMatcherFQN;
    private readonly null|string $_validatorFQN;
    private readonly null|string $_profileInterfaceFQN;

    /**
     * @param null|string $valueMatcherFQN Core class that matches values against patterns.
     * @param null|string $validatorFQN Core class that holds the validation rule registry.
     * @param null|string $profileInterfaceFQN Core interface implemented by profile classes.
     */
    public function __construct(
        null|string $valueMatcherFQN = null,
        null|string $validatorFQN = null,
        null|string $profileInterfaceFQN = null,
    ) {
        $this->_valueMatcherFQN = $valueMatcherFQN;
        $this->_validatorFQN = $validatorFQN;
        $this->_profileInterfaceFQN = $profileInterfaceFQN;
    }

    public function getValueMatcherFQN(): null|string
    {
        return $this->_valueMatcherFQN;
    }

    public function getValidatorFQN(): null|string
    {
        return $this->_validatorFQN;
    }

    public function getProfileInterfaceFQN(): null|string
    {
        return $this->_profileInterfaceFQN;
    }
}
