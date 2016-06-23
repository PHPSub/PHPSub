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

use PHPSub\Command\ToolbeltCommand;
use PHPSub\Plugin\Process\ParameterMapping\ProcessArgumentMapping;
use PHPSub\Plugin\Process\ParameterMapping\ProcessOptionMapping;
use PHPSub\Plugin\Process\ParameterMapping\ProcessParameterMappingFactory;
use PHPSub\Plugin\Process\ParameterMapping\ProcessParameterMappingInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class StartProcessCommand extends ToolbeltCommand
{
    /** @var string */
    protected $processCommand;
    /** @var string */
    protected $cwd;
    /** @var array */
    protected $envVars;
    /** @var mixed */
    protected $input;
    /** @var int */
    protected $timeout;
    /** @var bool */
    protected $tty;
    /** @var bool */
    protected $inheritEnv;
    /** @var array */
    protected $processOptions;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        $this->timeout        = 60;
        $this->processOptions = [];
        $this->envVars        = [];
        $this->inheritEnv     = false;
        $this->tty            = false;

        parent::__construct($name);
    }

    /**
     * @param string $processCommand
     *
     * @return $this
     */
    public function setProcessCommand($processCommand)
    {
        $this->processCommand = $processCommand;

        return $this;
    }

    /**
     * @param string $cwd
     *
     * @return $this
     */
    public function setCurrentWorkDirectory($cwd)
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * @param mixed $input
     *
     * @return $this
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableTimeout()
    {
        $this->setTimeout(null);

        return $this;
    }

    /**
     * @param array $envVars
     *
     * @return $this
     */
    public function setEnvVars(array $envVars = [])
    {
        $this->envVars = $envVars;

        return $this;
    }

    /**
     * @param boolean $tty
     *
     * @return $this
     */
    public function setTty($tty)
    {
        $this->tty = $tty;

        return $this;
    }

    /**
     * @param boolean $inheritEnv
     *
     * @return $this
     */
    public function setInheritEnv($inheritEnv)
    {
        $this->inheritEnv = $inheritEnv;

        return $this;
    }

    /**
     * @param string $processOption
     *
     * @return $this
     */
    public function addProcessOption($processOption)
    {
        $this->processOptions[] = $processOption;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $process = $this->getProcess($input);

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('<info>Executing: ' . $process->getCommandLine() . '</info>');
        }

        $process->run(
            function ($type, $buffer) use ($output) {
                if (Process::ERR === $type) {
                    $output->write('<error>' . $buffer . '</error>');
                } else {
                    $output->write($buffer);
                }
            }
        );

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('<info>Exit code: ' . $process->getExitCode() . '</info>');
        }

        return $process->getExitCode();
    }

    /**
     * @param InputInterface $input
     *
     * @return Process
     */
    protected function getProcess(InputInterface $input)
    {
        $process = new Process($this->processCommand);
        $process
            ->setWorkingDirectory($this->cwd)
            ->setInput($this->input ? $this->input : $input)
            ->setTimeout($this->timeout)
            ->setTty($this->tty)
        ;

        if ($this->inheritEnv) {
            $process->setEnv(array_replace($_ENV, $_SERVER, $this->envVars));
        } else {
            $process->setEnv($this->envVars);
        }

        return $process;
    }
}
