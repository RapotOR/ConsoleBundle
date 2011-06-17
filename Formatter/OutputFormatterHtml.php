<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sf2gen\Bundle\ConsoleBundle\Formatter;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Formatter class for console output.
 *
 * @author ndmf
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * 
 * @api
 */
class OutputFormatterHtml extends OutputFormatter
{
    protected $decorated;
    protected $styles = array();

    /**
     * Initializes console output formatter.
     *
     * @param   Boolean $decorated  Whether this formatter should actually decorate strings
     * @param   array   $styles     Array of "name => FormatterStyle" instance
     *
     * @api
     */
    public function __construct($decorated = null, array $styles = array())
    {
        $this->decorated = (Boolean) $decorated;

        $this->setStyle('error',    new OutputFormatterStyleHtml('white', 'red'));
        $this->setStyle('info',     new OutputFormatterStyleHtml('green'));
        $this->setStyle('comment',  new OutputFormatterStyleHtml('yellow'));
        $this->setStyle('question', new OutputFormatterStyleHtml('black', 'cyan'));

        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }
    }
    /**
     * Replaces style of the output.
     *
     * @param array $match
     *
     * @return string The replaced style
     */
    private function replaceStyle($match)
    {
        if (!$this->isDecorated()) {
            return $match[2];
        }

        if (isset($this->styles[strtolower($match[1])])) {
            $style = $this->styles[strtolower($match[1])];
        } else {
            $style = $this->createStyleFromString($match[1]);

            if (false === $style) {
                return $match[0];
            }
        }

        return $style->apply($this->format($match[2]));
    }


    /**
     * Tries to create new style instance from string.
     *
     * @param   string  $string
     *
     * @return  Symfony\Component\Console\Format\FormatterStyle|Boolean false if string is not format string
     */
    private function createStyleFromString($string)
    {
        if (!preg_match_all('/([^=]+)=([^;]+)(;|$)/', strtolower($string), $matches, PREG_SET_ORDER)) {
            return false;
        }

        $style = new OutputFormatterStyleHtml();
        foreach ($matches as $match) {
            array_shift($match);

            if ('fg' == $match[0]) {
                $style->setForeground($match[1]);
            } elseif ('bg' == $match[0]) {
                $style->setBackground($match[1]);
            } else {
                $style->setOption($match[1]);
            }
        }

        return $style;
    }
}
