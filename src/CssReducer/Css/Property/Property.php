<?php

/*
 * This file is part of the css-reducer
 *
 * (c) Besnik Brahimi <besnik.br@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CssReducer\Css\Property;

use CssReducer\Log\LoggerInterface;


class Property
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $inputs = array();

    /**
     * @param null|string $name
     * @param null|string $value
     * @param LoggerInterface $logger
     */
    public function __construct($name = null, $value = null, LoggerInterface $logger = null)
    {
        if ($name !== null && $value !== null) {
            $this->parse($name, $value);
        }

        $this->logger = $logger;
    }

    /**
     * @param $name
     * @param $value
     */
    public function parse($name, $value)
    {
        $isImportant = false;

        if (strpos($value, '!important') !== false) {
            $value = str_replace('!important', '', $value);
            $isImportant = true;
        }

        $this->inputs[] = array(
            'name' => $name,
            'value' => $value,
            'isImportant' => $isImportant,
        );
    }

    /**
     * @param Property $newProperty
     */
    public function merge(Property $newProperty)
    {
        $name = null;
        $value = null;
        $isImportant = null;

        extract($newProperty->reduce());

        if ($isImportant) {
            $value .= '!important';
        }

        $this->parse($name, $value);
    }

    /**
     * @throws \Exception
     * @return array
     */
    public function reduce()
    {
        return $this->override($this->inputs);
    }

    /**
     * @param $inputs
     * @throws \Exception
     * @return array
     */
    protected function override(array $inputs)
    {
        if (count($inputs) == 0) {
            throw new \Exception('There are no inputs.');
        }

        $foundAnImportantItem = false;
        $indexOfItemToReturn = 0;

        foreach ($inputs as $index => $input) {
            if ($foundAnImportantItem && !$input['isImportant']) {
                continue;
            }

            if (!$foundAnImportantItem && $input['isImportant']) {
                $foundAnImportantItem = true;
            }

            $indexOfItemToReturn = $index;
        }

        return $inputs[$indexOfItemToReturn];
    }

    /**
     * @param $value
     * @return string
     */
    public static function shortDimension($value)
    {
        $matches = array();

        // 0px => 0
        if (preg_match('~(0|0\.[0-9]*)(%|cm|ex|in|mm|pc|pt|px)~i', $value, $matches)) {
            // 0.*
            if ($matches[1] != '0') {
                // 0.9em => .9em
                if ($matches[2] == 'em') {
                    return str_replace('0.', '.', $matches[1]) . $matches[2];
                }
            } // 0
            else {
                return '0';
            }

        }

        return $value;
    }
}
