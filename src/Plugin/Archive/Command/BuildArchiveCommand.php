<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Archive\Command;

use KevinGH\Box\Application as BoxApplication;
use PHPSub\Command\ToolbeltCommand;
use PHPSub\Plugin\PluginAwareCommand;
use PHPSub\Plugin\PluginAwareTrait;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class BuildArchiveCommand extends ToolbeltCommand implements PluginAwareCommand
{
    use PluginAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('toolbelt:build')
            ->setDescription('Build the toolbelt PHAR archive')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->plugin->getConfiguration();

        $filesystem = new Filesystem();
        $filesystem->remove($config['build_dir']);
        $filesystem->mkdir($config['build_dir']);

        $boxBuildFilePath = $this->dumpBoxConfigFile($config);

        $returnCode = $this->runArchiveBuild($output, $boxBuildFilePath);

        if ($returnCode == 0) {
            $archivePath = isset($config['box_build_config']['output'])
                ? $config['box_build_config']['output']
                : 'default.phar'
            ;

            $output->writeln(
                sprintf('<info>Archive successfully built:</info> <comment>%s</comment>', $archivePath)
            );
        }
    }

    /**
     * @param OutputInterface $output
     * @param string          $boxBuildFilePath
     *
     * @return int
     */
    private function runArchiveBuild(OutputInterface $output, $boxBuildFilePath)
    {
        $pharOutputDir = dirname($this->getPharOutputPath());

        if (!is_dir($pharOutputDir)) {
            mkdir($pharOutputDir);
        }

        $reflector = new ReflectionClass(BoxApplication::class);
        define('BOX_PATH', realpath(dirname($reflector->getFileName()) . '/../../../../'));

        $boxApp  = new BoxApplication();
        $command = $boxApp->find('build');

        $input      = new ArrayInput(['-c' => $boxBuildFilePath]);
        $returnCode = $command->run($input, $output);

        return $returnCode;
    }

    /**
     * @param array $config
     *
     * @return string
     */
    private function dumpBoxConfigFile(array $config)
    {
        $this->copyScriptsArtifacts($config['scripts_dir'], $config['build_dir']);

        $boxConfig = $config['box_build_config'];

        $boxConfig['directories'][] = $this->plugin->getToolbeltRelativePath(
            $config['build_dir'] . DIRECTORY_SEPARATOR . 'artifacts'
        );

        if (!isset($boxConfig['map'])) {
            $boxConfig['map'] = [];
        }

        $boxConfig['map'][] = [
            $this->plugin->getToolbeltRelativePath(
                $config['build_dir'] . DIRECTORY_SEPARATOR . 'artifacts'
            ) => 'artifacts'
        ];

        $boxBuildFileContent = json_encode($boxConfig);
        $boxBuildFilePath    = $config['build_dir'] . DIRECTORY_SEPARATOR . 'box.json';

        if (file_exists($boxBuildFilePath)) {
            unlink($boxBuildFilePath);
        }

        file_put_contents($boxBuildFilePath, $boxBuildFileContent);

        return $boxBuildFilePath;
    }

    /**
     * @param string $scriptsDir
     * @param string $buildDir
     */
    private function copyScriptsArtifacts($scriptsDir, $buildDir)
    {
        $fs = new Filesystem();

        $fs->mirror($scriptsDir, $buildDir . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'scripts');
    }

    /**
     * @return string
     */
    private function getPharOutputPath()
    {
        $boxConfig = $this->plugin->getConfiguration()['box_build_config'];

        $outputPath = 'default.phar';
        if (isset($boxConfig['output'])) {
            $outputPath = $boxConfig['output'];
        }

        if (strpos($outputPath, '/') === 0) {
            return $outputPath;
        }

        return $this->plugin->normalizePath('..' . DIRECTORY_SEPARATOR . $outputPath);
    }
}
