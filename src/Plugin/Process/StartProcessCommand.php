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
    private $processCommand;
    /** @var string */
    private $cwd;
    /** @var array */
    private $envVars;
    /** @var mixed */
    private $input;
    /** @var int */
    private $timeout;
    /** @var array */
    private $processOptions;
    /** @var ProcessParameterMappingInterface[] */
    private $mappings;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        $this->mappings = [];

        parent::__construct($name);

        $this->timeout        = 60;
        $this->processOptions = [];
        $this->envVars        = [];
    }

    /**
     * @param string $parameterName
     * @param string $type
     * @param array  $options
     *
     * @return $this
     */
    public function addParameterMapping(
        $parameterName,
        $type,
        array $options = []
    ) {
        $factory = new ProcessParameterMappingFactory();

        $this->registerParameterMapping(
            $factory->buildMapping($parameterName, $type, $options)
        );

        return $this;
    }

    /**
     * @param ProcessParameterMappingInterface $mapping
     */
    private function registerParameterMapping(ProcessParameterMappingInterface $mapping)
    {
        if ($mapping instanceof ProcessArgumentMapping) {
            if (!$this->getDefinition()->hasArgument($mapping->getParameterName())) {
                throw new \LogicException(
                    sprintf(
                        'The argument "%s" does not exists thus can\'t be mapped.',
                        $mapping->getParameterName()
                    )
                );
            }
        } elseif ($mapping instanceof ProcessOptionMapping) {
            if (!$this->getDefinition()->hasOption($mapping->getParameterName())) {
                throw new \LogicException(
                    sprintf(
                        'The option "%s" does not exists thus can\'t be mapped.',
                        $mapping->getParameterName()
                    )
                );
            }
        }

        $this->mappings[] = $mapping;
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
        $parameters = [];
        foreach ($this->mappings as $mapping) {
            if ($mapping instanceof ProcessArgumentMapping) {
                $parameter = $input->getArgument($mapping->getParameterName());

                if (null !== $filter = $mapping->getFilter()) {
                    $parameter = $filter($parameter);
                }
            } elseif ($mapping instanceof ProcessOptionMapping) {
                $optionValue = $input->getOption($mapping->getParameterName());

                if (null !== $filter = $mapping->getFilter()) {
                    $parameter = $filter($optionValue);
                } else {
                    $optionDefinition = $this->getDefinition()->getOption($mapping->getParameterName());
                    $parameter = sprintf(
                        '%s %s',
                        $mapping->useShortcut()
                            ? '-' . $optionDefinition->getShortcut()
                            : '--' . $optionDefinition->getName(),
                        $optionValue
                    );
                }
            } else {
                continue;
            }

            if (null !== $parameter) {
                $parameters[] = $parameter;
            }
        }

        $builder = new ProcessBuilder();
        $builder
            ->setPrefix($this->processCommand)
            ->setArguments($parameters)
            ->setWorkingDirectory($this->cwd)
            ->setInput($this->input ? $this->input : $input)
            ->addEnvironmentVariables($this->envVars)
            ->setTimeout($this->timeout)
        ;

        foreach ($this->processOptions as $name => $value) {
            $builder->setOption($name, $value);
        }

        $process = $builder->getProcess();

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
}
