<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\EmbeddedScript\Command;

use PHPSub\Command\ToolbeltCommand;
use PHPSub\Plugin\EmbeddedScript\EmbeddedScriptPlugin;
use PHPSub\Plugin\PluginAwareCommand;
use PHPSub\Plugin\PluginAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class DumpScriptsCommand extends ToolbeltCommand implements PluginAwareCommand
{
    use PluginAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('toolbelt:dump-scripts')
            ->setDescription('Dump the embedded scripts to filesystem')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EmbeddedScriptPlugin $plugin */
        $plugin = $this->plugin;

        $plugin->dumpScripts();

        $output->writeln('<info>Script dumped successfully</info>');
    }
}
