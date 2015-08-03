<?php namespace PHPFHIR\Utilities;

/**
 * Class FileUtils
 * @package PHPFHIR\Utilities
 */
abstract class FileUtils
{
    /**
     * @param string $outputPath
     * @param string $namespace
     * @return bool
     */
    public static function createDirsFromNS($outputPath, $namespace)
    {
        if ('\\' === $namespace)
            return true;

        $path = trim($outputPath, "\\");
        foreach(explode('\\', $namespace) as $dirName)
        {
            $path = sprintf('%s/%s', $path, $dirName);
            if (!is_dir($path))
            {
                $made = (bool)@mkdir($path);
                if (false === $made)
                    throw new \RuntimeException('Unable to create directory at path "'.$path.'"');
            }
        }

        return true;
    }
}