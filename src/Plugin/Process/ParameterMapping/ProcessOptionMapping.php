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
class ProcessOptionMapping implements ProcessParameterMappingInterface
{
    const PARAMETER_TYPE_OPTION = 'option';

    /** @var string */
    private $optionName;
    /** @var callable|null */
    private $filter;
    /** @var bool */
    private $useShortcut;

    /**
     * @param string   $optionName
     * @param bool     $useShortcut
     * @param callable $filter
     */
    public function __construct($optionName, $useShortcut = false, callable $filter = null)
    {
        $this->optionName  = $optionName;
        $this->filter      = $filter;
        $this->useShortcut = $useShortcut;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterName()
    {
        return $this->optionName;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return bool
     */
    public function useShortcut()
    {
        return $this->useShortcut;
    }

    /**
     * {@inheritdoc}
     */
    public static function getParameterType()
    {
        return self::PARAMETER_TYPE_OPTION;
    }
}
