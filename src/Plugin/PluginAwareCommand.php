<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
interface PluginAwareCommand
{
    /**
     * @param Plugin $plugin
     */
    public function setPlugin(Plugin $plugin);
}
