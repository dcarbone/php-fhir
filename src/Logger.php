<?php namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

// if the user is running php version 8.0 or greater, the psr/log interface implementation
// has a stricter definition, and we must be able to support that while maintaining backwards
// compatibility
if (80000 <= PHP_VERSION_ID) {
    /**
     * Class Logger
     * @package DCarbone\PHPFHIR
     */
    class Logger extends AbstractLogger
    {
        /** @var LoggerInterface */
        protected $actualLogger;

        /** @var string */
        protected $breakLevel;

        /**
         * Logger constructor.
         * @param LoggerInterface $actualLogger
         * @param string $breakLevel
         */
        public function __construct(LoggerInterface $actualLogger, $breakLevel = LogLevel::WARNING)
        {
            $this->actualLogger = $actualLogger;
            $this->breakLevel = $breakLevel;
        }

        /**
         * @param string|\Stringable $action
         */
        public function startBreak(string|\Stringable $action)
        {
            $this->log($this->breakLevel, substr(sprintf('%\'-5s Start %s %1$-\'-75s', '-', $action), 0, 75));
        }

        /**
         * @param string $level
         * @param string|\Stringable $message
         * @param array $context
         */
        public function log($level, string|\Stringable $message, array $context = array()): void
        {
            $this->actualLogger->log($level, $message, $context);
        }

        /**
         * @param string|\Stringable $action
         */
        public function endBreak(string|\Stringable $action)
        {
            $this->log($this->breakLevel, substr(sprintf('%\'-5s End %s %1$-\'-75s', '-', $action), 0, 75));
        }
    }
} else {
    /**
     * Class Logger
     * @package DCarbone\PHPFHIR
     */
    class Logger extends AbstractLogger
    {
        /** @var LoggerInterface */
        protected $actualLogger;

        /** @var string */
        protected $breakLevel;

        /**
         * Logger constructor.
         * @param LoggerInterface $actualLogger
         * @param string $breakLevel
         */
        public function __construct(LoggerInterface $actualLogger, $breakLevel = LogLevel::WARNING)
        {
            $this->actualLogger = $actualLogger;
            $this->breakLevel = $breakLevel;
        }

        /**
         * @param string $action
         */
        public function startBreak($action)
        {
            $this->log($this->breakLevel, substr(sprintf('%\'-5s Start %s %1$-\'-75s', '-', $action), 0, 75));
        }

        /**
         * @param string $level
         * @param string $message
         * @param array $context
         */
        public function log($level, $message, array $context = array())
        {
            $this->actualLogger->log($level, $message, $context);
        }

        /**
         * @param string $action
         */
        public function endBreak($action)
        {
            $this->log($this->breakLevel, substr(sprintf('%\'-5s End %s %1$-\'-75s', '-', $action), 0, 75));
        }
    }
}
