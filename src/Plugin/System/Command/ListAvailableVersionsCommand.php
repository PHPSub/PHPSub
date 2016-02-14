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

use PHPSub\Command\ToolbeltCommand;
use PHPSub\Plugin\PluginAwareCommand;
use PHPSub\Plugin\PluginAwareTrait;
use PHPSub\Plugin\System\Manifest\Manifest;
use PHPSub\Plugin\System\SystemPlugin;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class ListAvailableVersionsCommand extends ToolbeltCommand implements PluginAwareCommand
{
    use PluginAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('toolbelt:versions:list')
            ->setDescription('Lists the existing versions of the toolbelt')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Current version: <info>'.$this->getApplication()->getVersion().'</info>');

        /** @var SystemPlugin $plugin */
        $plugin = $this->plugin;

        $manifestManager = $plugin->getManifestManager();

        $manifest = $manifestManager->loadManifest();

        $table = new Table($output);
        $table
            ->setHeaders(['Version', 'URL'])
            ->setRows(
                array_map(
                    function ($item) {
                        return [$item['version'], $item['url']];
                    },
                    $manifest->getSortedEntries(Manifest::SORT_VERSION_DESC)
                )
            )
        ;

        $table->render();
    }
}
