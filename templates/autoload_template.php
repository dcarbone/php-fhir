<?php

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

return <<<STRING
<?php namespace %s;

%s

class PHPFHIRAutoloader
{
    const ROOT_DIR = __DIR__;

    /** @var array */
    private static \$_classMap = %s;

    /** @var bool */
    private static \$_registered = false;

    /**
     * @return bool
     * @throws \Exception
     */
    public static function register()
    {
        if (self::\$_registered)
            return self::\$_registered;
        return self::\$_registered = spl_autoload_register(array(__CLASS__, 'loadClass'), true);
    }

    /**
     * @return bool
     */
    public static function unregister()
    {
        self::\$_registered = !spl_autoload_unregister(array(__CLASS__, 'loadClass'));
        return !self::\$_registered;
    }

    /**
     * Please see associated documentation for more information on what this method looks for.
     *
     * @param string \$class
     * @return bool|null
     */
    public static function loadClass(\$class)
    {
        if (isset(self::\$_classMap[\$class]))
            return (bool)require sprintf('%%s/%%s', self::ROOT_DIR, self::\$_classMap[\$class]);
        return null;
    }
}
STRING;
