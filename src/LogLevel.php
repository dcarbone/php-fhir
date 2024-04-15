<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR;

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

use DCarbone\PHPFHIR\Enum\ValuesTrait;
use Psr\Log\LogLevel as PLogLevel;

enum LogLevel: string
{
    use ValuesTrait;

    case EMERGENCY = PLogLevel::EMERGENCY;
    case ALERT = PLogLevel::ALERT;
    case CRITICAL = PLogLevel::CRITICAL;
    case ERROR = PLogLevel::ERROR;
    case WARNING = PLogLevel::WARNING;
    case NOTICE = PLogLevel::NOTICE;
    case INFO = PLogLevel::INFO;
    case DEBUG = PLogLevel::DEBUG;
}