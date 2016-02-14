<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Distribution\Command;

use PHPSub\Command\ToolbeltCommand;
use PHPSub\Plugin\Distribution\DistributionPlugin;
use PHPSub\Plugin\System\Manifest\Exception\ManifestFetchingException;
use PHPSub\Plugin\PluginAwareCommand;
use PHPSub\Plugin\PluginAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class PublishArchiveCommand extends ToolbeltCommand implements PluginAwareCommand
{
    use PluginAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('toolbelt:publish')
            ->setDescription('Publish the toolbelt PHAR archive')
            ->addOption(
                'build-version',
                null,
                InputOption::VALUE_OPTIONAL,
                'Archive version to publish (if not provided, the most recent git tag will be used)'
            )
            ->addOption(
                'phar',
                null,
                InputOption::VALUE_OPTIONAL,
                'Archive file path (default: distribution.phar_path config value)'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DistributionPlugin $plugin */
        $plugin         = $this->plugin;
        $questionHelper = $this->getHelper('question');


        if (null === $pharPath = $input->getOption('phar')) {
            $pharPath = $plugin->getConfiguration()['phar_path'];
        }

        if (null === $version = $input->getOption('build-version')) {
            $version = $this->runGitCommand($pharPath, 'git describe --tags HEAD');
        }

        $distributionManager = $plugin->getDistributionManager();
        $manifestManager     = $plugin->getManifestManager();

        try {
            $manifest = $manifestManager->loadManifest();
        } catch (ManifestFetchingException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            $question = new ConfirmationQuestion(
                'Do you still want to plublish the archive? '.
                '<comment>This will empty the remote manifest (if exists)!</comment>'.
                '<question>[y/n]</question> (default: <info>no</info>):',
                false
            );

            if (!$questionHelper->ask($input, $output, $question)) {
                return 1;
            }

            $manifest = $manifestManager->buildManifest([]);
        }

        if (false !== $manifestManager->checkIdenticalVersion($manifest, $version)) {
            $output->writeln(
                sprintf('<error>An archive with the same version is already published (%s)</error>', $version)
            );
            $output->writeln(
                '<comment>Publishing an archive with the same version won\'t trigger new installations</comment>'
            );

            return 1;
        }

        $newVersion = $manifestManager->isNewVersionAvailable($manifest, $version);
        if (false !== $newVersion && !$this->askToContinue(
                sprintf('<comment>A more recent version is already published (%s)</comment>', $newVersion['version']),
                $input,
                $output
            )
        ) {
            return 1;
        }

        $identicalArchive = $manifestManager->checkIdenticalSignature(
            $manifest,
            $distributionManager->getArchiveSignature($pharPath)
        );
        if (false !== $identicalArchive && !$this->askToContinue(
                sprintf(
                    "<comment>An archive with the same signature is already published :\n%s</comment>",
                    json_encode($identicalArchive, JSON_PRETTY_PRINT)
                ),
                $input,
                $output
            )
        ) {
            return 1;
        }

        $manifestEntry = $distributionManager->distributeArchive($version, $pharPath, $manifest);

        $output->writeln('<info>Archive successfully published!</info>');

        $output->writeln(sprintf('<comment>%s</comment>', json_encode($manifestEntry, JSON_PRETTY_PRINT)));

        return 0;
    }

    /**
     * Runs a Git command on the repository.
     *
     * @param string $baseFile The file inside Git working dir to start search from
     * @param string $command The command.
     *
     * @return string The trimmed output from the command.
     *
     */
    private function runGitCommand($baseFile, $command)
    {
        $path    = dirname($baseFile);
        $process = new Process($command, $path);

        if (0 === $process->run()) {
            return trim($process->getOutput());
        }

        throw new \RuntimeException(
            sprintf(
                'The tag or commit hash could not be retrieved from "%s": %s',
                $path,
                $process->getErrorOutput()
            )
        );
    }

    /**
     * @param string          $issueMessage
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    private function askToContinue($issueMessage, InputInterface $input, OutputInterface $output)
    {
        $output->writeln($issueMessage);

        $questionHelper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            ' Do you still want to publish this archive?<question>[y/n]</question> (default: <info>no</info>):', false);

        if (!$questionHelper->ask($input, $output, $question)) {
            return false;
        }

        return true;
    }
}
