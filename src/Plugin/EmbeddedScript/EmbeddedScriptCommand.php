<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\EmbeddedScript;

use PHPSub\Plugin\PluginAwareCommand;
use PHPSub\Plugin\PluginAwareTrait;
use PHPSub\Plugin\Process\MappedArgumentProcessCommand;
use PHPSub\Plugin\Process\StartProcessCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
abstract class EmbeddedScriptCommand extends MappedArgumentProcessCommand implements PluginAwareCommand
{
    use PluginAwareTrait;

    const DEFAULT_SCRIPT_ROOT_DIR = '/tmp/toolbelt-scripts';

    /** @var string */
    private $scriptFilePath;
    /** @var EmbeddedScriptPlugin */
    private $embeddedScriptPlugin;

    /**
     * Sets the scriptFilePath attribute
     *
     * @param mixed $scriptFilePath
     *
     * @return $this
     */
    public function setScriptFilePath($scriptFilePath)
    {
        $this->scriptFilePath = $scriptFilePath;

        return $this;
    }

    /**
     * @param EmbeddedScriptPlugin $plugin
     */
    public function setEmbeddedScriptPlugin(EmbeddedScriptPlugin $plugin)
    {
        $this->embeddedScriptPlugin = $plugin;
    }

    /**
     * @return string
     */
    private function getScriptsRootDir()
    {
        if (null !== $this->plugin) {
            $plugin = $this->plugin;
        } else {
            $plugin = $this->embeddedScriptPlugin;
        }

        $pluginConfig = $plugin->getConfiguration();

        if (array_key_exists('dump_folder', $pluginConfig)) {
            return $pluginConfig['dump_folder'];
        }

        return self::DEFAULT_SCRIPT_ROOT_DIR;
    }

    /**
     * @return bool
     */
    private function isScriptsDumped()
    {
        return is_dir($this->getScriptsRootDir());
    }

    /**
     * @return string
     */
    private function getAbsoluteScriptPath()
    {
        if (null === $this->scriptFilePath) {
            throw new \LogicException('A script file path must be set in the ScriptCommand.');
        }

        return $this->getScriptsRootDir() . DIRECTORY_SEPARATOR . $this->scriptFilePath;
    }

    /**
     * {@inheritdoc}
     */
    public final function setProcessCommand($processCommand)
    {
        throw new \LogicException('You can\'t use the setProcessCommand() method in SubScriptCommand context. ' .
            'Please use setScriptFilePath() instead.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scriptPath = $this->getAbsoluteScriptPath();

        $askToDumpScripts = false;

        if (!$this->isScriptsDumped()) {
            $output->writeln(
                sprintf(
                    '<error>The scripts seams not to be dumped. The %s command May help.</error>',
                    'toolbelt:dump-scripts'
                )
            );

            $askToDumpScripts = true;
        }

        if (!is_executable($scriptPath)) {
            $output->writeln(
                sprintf(
                    '<error>The script `%s` can\'t be found or it isn\'t executable. The %s command May help.</error>',
                    $this->scriptFilePath,
                    'toolbelt:dump-scripts'
                )
            );

            $askToDumpScripts = true;
        }

        if ($askToDumpScripts) {
            $result = $this->askToDumpScripts($input, $output);

            if ($result !== 0) {
                return $result;
            }
        }

        $this->setProcessPrefix($scriptPath);

        return parent::execute($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function askToDumpScripts(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Do you want me to dump the scripts for you? <question>[y/n]</question> (default: <info>yes</info>):',
            true
        );

        if (!$helper->ask($input, $output, $question)) {
            return 1;
        }

        $command = $this->getApplication()->find('toolbelt:dump-scripts');

        return $command->run(new ArrayInput([]), $output);
    }
}
