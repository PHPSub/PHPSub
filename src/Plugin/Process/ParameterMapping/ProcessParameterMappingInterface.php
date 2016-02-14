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
interface ProcessParameterMappingInterface
{
    /**
     * Gets the parameter name
     *
     * @return string
     */
    public function getParameterName();

    /**
     * Get the optional filter to apply on parameter before reusing it in the lower script
     *
     * @return callable|null
     */
    public function getFilter();

    /**
     * Gets the parameter type
     *
     * @return string
     */
    public static function getParameterType();
}
