<?php

/*
 * This file is part of the css-reducer
 *
 * (c) Besnik Brahimi <besnik.br@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CssReducer;

use CssReducer\Log\LoggerInterface;


/**
 *
 */
class Parser
{
    /**
     * @var Log\LoggerInterface
     */
    protected $logger;

    /**
     *
     * @var string
     */
    protected $pattern = "~([^{]*)\{([^}]*+)\}~ms";

    /**
     *
     * @var string
     */
    protected $propertiesPattern = "~([^ :;]*):([^;]*)[;]?~ms";

    /**
     *
     * @var array
     */
    protected $options = array(
        'split_selectors' => false,
    );

    /**
     * @param Log\LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     *
     * @param mixed $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function setOption($name, $value)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" does not exist.' .
                'The following options are allowed: %s', $name, join(', ', array_keys($this->options))));
        }

        $this->options[$name] = $value;
    }

    /**
     *
     * @param $name
     * @return mixed
     */
    public function getOption($name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : $name;
    }

    /**
     * Splits css selectors, e.g.  a, b {} => a {} b {}
     *
     * @param array $cssDefinitions
     * @return array
     */
    protected function splitSelectors(array $cssDefinitions)
    {
        $cssDefinitionSplitted = array();

        $n = 0;

        foreach ($cssDefinitions as $cssBlock) {
            $selectors = key($cssBlock);
            $properties = reset($cssBlock);

            if (strpos($selectors, ',') !== false) {
                foreach (explode(',', $selectors) as $selector) {
                    $cssDefinitionSplitted[$n][trim($selector)] = $properties;
                    $n++;
                }
            } else {
                $cssDefinitionSplitted[$n][$selectors] = $properties;
            }

            $n++;
        }

        return $cssDefinitionSplitted;
    }

    /**
     *
     * @param string $fileUrlOrCss
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function load($fileUrlOrCss)
    {
        $content = "";

        if (is_array($fileUrlOrCss)) {
            foreach ($fileUrlOrCss as $part) {
                $content .= $this->load($part);
            }
        } else {
            if (strpos($fileUrlOrCss, '{') !== false) {
                $content = $fileUrlOrCss;
            } elseif (strpos($fileUrlOrCss, 'http') !== false || file_exists($fileUrlOrCss)) {
                $content = file_get_contents($fileUrlOrCss);
            } else {
                throw new \InvalidArgumentException("File '$fileUrlOrCss' not found.");
            }
        }

        return $content;
    }

    /**
     *
     * @param array $cssDefinitions
     * @return array
     */
    protected function parseProperties(array $cssDefinitions = array())
    {
        foreach ($cssDefinitions as $index => $cssBlock) {
            $selector = key($cssBlock);
            $properties = reset($cssBlock);

            $matches = array();

            if (preg_match_all($this->propertiesPattern, $properties, $matches)) {
                $properties = array_combine($matches[1], $matches[2]);
            } else {
                $properties = array();
            }

            foreach ($properties as $name => $value) {
                unset($properties[$name]);
                $properties[trim($name)] = trim($value);
            }

            $cssDefinitions[$index][$selector] = $properties;
        }

        return $cssDefinitions;
    }

    /**
     *
     * @param $fileUrlOrCss
     * @throws \InvalidArgumentException
     * @return array
     */
    public function parse($fileUrlOrCss)
    {
        $content = $this->load($fileUrlOrCss);

        $minifier = new Minifier();
        $content = $minifier->minify($content, array(
            'remove_comments' => true,
            'remove_whitespaces' => true,
            'remove_tabs' => true,
            'remove_newlines' => true,
        ));

        $matches = array();

        if (!preg_match_all($this->pattern, $content, $matches)) {
            throw new \InvalidArgumentException("Nothing parsed.");
        }

        $cssDefinitions = array();

        for ($i = 0; $i < count($matches[0]); $i++) {
            $cssDefinitions[] = array(
                trim($matches[1][$i]) => trim($matches[2][$i])
            );
        }

        if ($this->getOption('split_selectors')) {
            $cssDefinitions = $this->splitSelectors($cssDefinitions);
        }

        $cssDefinitions = $this->parseProperties($cssDefinitions);

        return $cssDefinitions;
    }
}
