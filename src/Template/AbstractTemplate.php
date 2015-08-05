<?php namespace PHPFHIR\Template;

/**
 * Class AbstractTemplate
 * @package PHPFHIR\Template
 */
abstract class AbstractTemplate
{
    /** @var array */
    protected $documentation;

    /**
     * @return array
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    /**
     * @param string|array|null $documentation
     */
    public function setDocumentation($documentation)
    {
        if (null !== $documentation)
        {
            if (is_string($documentation))
                $documentation = array($documentation);

            if (is_array($documentation))
                $this->documentation = $documentation;
            else
                throw new \InvalidArgumentException('Documentation expected to be array, string, or null.');
        }
    }
}