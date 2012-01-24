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
        $this->setDecorated($decorated);

        $this->setStyle('error',    new OutputFormatterStyleHtml('white', 'red'));
        $this->setStyle('info',     new OutputFormatterStyleHtml('green'));
        $this->setStyle('comment',  new OutputFormatterStyleHtml('yellow'));
        $this->setStyle('question', new OutputFormatterStyleHtml('black', 'cyan'));

        foreach ($styles as $name => $style) {
            $this->setStyle($name, $style);
        }
    }
}
