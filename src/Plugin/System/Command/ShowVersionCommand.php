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
use PHPSub\Plugin\System\SystemPlugin;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class ShowVersionCommand extends ToolbeltCommand implements PluginAwareCommand
{
    use PluginAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('toolbelt:versions:show')
            ->setDescription('Show one version information')
            ->addArgument(
                'version',
                InputArgument::REQUIRED,
                'Version number'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SystemPlugin $plugin */
        $plugin = $this->plugin;

        $manifestManager = $plugin->getManifestManager();

        $manifest = $manifestManager->loadManifest();

        $version = $input->getArgument('version');
        if (null === $entry = $manifestManager->getEntryByVersion($manifest, $version)) {
            $output->writeln('<error>No archive matching version ' . $version . '</error>');

            return 1;
        }

        $output->writeln(sprintf('Version: <info>%s</info>', $entry['version']));
        $output->writeln('');

        $output->writeln(sprintf("\t%s: <comment>%s</comment>", "Name", $entry['name']));
        $output->writeln(sprintf("\t%s: <comment>%s</comment>", "URL", $entry['url']));
        $output->writeln(
            sprintf(
                "\t%s: <comment>%s</comment>",
                "Public key",
                isset($entry['publicKey']) ? $entry['publicKey'] : 'None'
            )
        );
        $output->writeln(sprintf("\t%s: <comment>%s</comment>", "Signature", $entry['sha1']));

        return 0;
    }
}
