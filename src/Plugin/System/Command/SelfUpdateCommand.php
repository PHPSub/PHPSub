<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\System\Command;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use PHPSub\Command\ToolbeltCommand;
use PHPSub\Plugin\PluginAwareCommand;
use PHPSub\Plugin\PluginAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class SelfUpdateCommand extends ToolbeltCommand implements PluginAwareCommand
{
    use PluginAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('toolbelt:self-update')
            ->setDescription('Updates the toolbelt to the latest version')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->plugin->getConfiguration();

        $manager = new Manager(Manifest::loadFile($config['manifest_url']));
        $manager->update($this->getApplication()->getVersion(), true);
    }
}
