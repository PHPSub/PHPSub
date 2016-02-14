<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Process;

use PHPSub\Plugin\Plugin;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
final class ProcessPlugin extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'process';
    }
}
