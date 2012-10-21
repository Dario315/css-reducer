<?php

/*
 * This file is part of the css-reducer
 *
 * (c) Besnik Brahimi <besnik.br@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CssReducer\Test\Css\Property;

use CssReducer\Css\Property\Color;


class ColorTest extends \PHPUnit_Framework_TestCase
{
    public function testOverwriting()
    {
        $color1 = new Color('color', 'blue');
        $color2 = new Color('color', 'red');
        $color3 = new Color('color', '#fff');

        $color1->merge($color2);
        $color1->merge($color3);

        $this->assertEquals(array(
            'name' => 'color',
            'value' => '#fff',
            'isImportant' => false,
        ), $color1->reduce());
    }

    public function testOverwritingWithImportantValue()
    {
        $color1 = new Color('color', 'blue!important');
        $color2 = new Color('color', 'red');
        $color3 = new Color('color', '#fff');

        $color1->merge($color2);
        $color1->merge($color3);

        $this->assertEquals(array(
            'name' => 'color',
            'value' => 'blue',
            'isImportant' => true,
        ), $color1->reduce());
    }


}
