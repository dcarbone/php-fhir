<?php namespace PHPFHIR;

use DCarbone\AbstractCollectionPlus;
use PHPFHIR\Utilities\SimpleXMLBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class XSDMap
 * @package PHPFHIR
 */
class XSDMap extends AbstractCollectionPlus
{
    /** @var string */
    protected $xsdPath;

    /**
     * @param string $xsdPath
     * @param null $_data
     */
    public function __construct($xsdPath, $_data = null)
    {
        $this->xsdPath = $xsdPath;

        if (is_array($_data))
        {
            parent::__construct($_data);
        }
        else
        {
            $xsdFiles = array();

            if (!file_exists($xsdPath.'fhir-all.xsd'))
                throw new \RuntimeException('Unable to locate "fhir-all.xsd"');

            $finder = new Finder();

            $finder->files()
                ->in($xsdPath)
                ->ignoreDotFiles(true)
                ->name('*.xsd')
                ->notName('fhir-*.xsd');

            foreach($finder as $file)
            {
                /** @var SplFileInfo $file */
                $xsdFiles[$file->getBasename(true)] = $file->getRealPath();
            }

            parent::__construct($xsdFiles);
        }
    }

    /**
     * @param array $data
     * @return static
     */
    protected function initNew(array $data = array())
    {
        return new static($this->xsdPath, $data);
    }

    /**
     * @return string
     */
    public function getXSDPath()
    {
        return $this->xsdPath;
    }

    /**
     * @param string $fileName
     * @return null|string
     */
    public function getFilePath($fileName)
    {
        if ('.xsd' !== substr($fileName, -4))
            $fileName = sprintf('%s.xsd', $fileName);

        if (isset($this[$fileName]))
            return $this[$fileName];

        return null;
    }

    /**
     * @param string $fileName
     * @return null|string
     */
    public function getContentsOfFile($fileName)
    {
        $filePath = $this->getFilePath($fileName);

        if (null === $filePath)
            return null;

        return file_get_contents($filePath);
    }

    /**
     * @param string $fileName
     * @return null|\SimpleXMLElement
     */
    public function getSXEOfFile($fileName)
    {
        $filePath = $this->getFilePath($fileName);

        if (null === $filePath)
            return null;

        return SimpleXMLBuilder::constructWithFilePath($filePath);
    }
}