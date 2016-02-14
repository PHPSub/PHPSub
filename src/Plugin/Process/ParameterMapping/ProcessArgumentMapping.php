<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Process\ParameterMapping;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class ProcessArgumentMapping implements ProcessParameterMappingInterface
{
    const PARAMETER_TYPE_ARGUMENT = 'argument';

    /** @var string */
    private $argumentName;
    /** @var callable|null */
    private $filter;

    /**
     * @param string   $argumentName
     * @param callable $filter
     */
    public function __construct($argumentName, callable $filter = null)
    {
        $this->argumentName = $argumentName;
        $this->filter        = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterName()
    {
        return $this->argumentName;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getParameterType()
    {
        return self::PARAMETER_TYPE_ARGUMENT;
    }
}
