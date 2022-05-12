<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

/*
 * Copyright 2016-2021 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/**
 * Class Logger
 * @package DCarbone\PHPFHIR
 */
class Logger extends AbstractLogger
{
    /** @var LoggerInterface */
    protected LoggerInterface $actualLogger;

    /** @var string */
    protected string $breakLevel;

    /**
     * Logger constructor.
     * @param LoggerInterface $actualLogger
     * @param string|\Stringable $breakLevel
     */
    public function __construct(LoggerInterface $actualLogger, string|\Stringable $breakLevel = LogLevel::WARNING)
    {
        $this->actualLogger = $actualLogger;
        $this->breakLevel = $breakLevel;
    }

    /**
     * @param string|\Stringable $action
     */
    public function startBreak(string|\Stringable $action): void
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
    public function endBreak(string|\Stringable $action): void
    {
        $this->log($this->breakLevel, substr(sprintf('%\'-5s End %s %1$-\'-75s', '-', $action), 0, 75));
    }
}