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

use PHPSub\Plugin\Process\ParameterMapping\ProcessArgumentMapping;
use PHPSub\Plugin\Process\ParameterMapping\ProcessOptionMapping;
use PHPSub\Plugin\Process\ParameterMapping\ProcessParameterMappingFactory;
use PHPSub\Plugin\Process\ParameterMapping\ProcessParameterMappingInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class MappedArgumentProcessCommand extends StartProcessCommand
{
    /** @var string */
    private $processPrefix;
    /** @var ProcessParameterMappingInterface[] */
    protected $mappings;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        $this->mappings = [];

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = new ProcessBuilder();
        $builder
            ->setPrefix($this->processPrefix)
            ->setArguments($this->buildArguments($input))
        ;

        foreach ($this->processOptions as $name => $value) {
            $builder->setOption($name, $value);
        }

        $process = $builder->getProcess();

        $this->processCommand = $process->getCommandLine();

        return parent::execute($input, $output);
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
     * @param InputInterface $input
     *
     * @return array
     */
    private function buildArguments(InputInterface $input)
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
                    $parameter        = sprintf(
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

        return $parameters;
    }

    /**
     * @param string $processPrefix
     *
     * @return $this
     */
    public function setProcessPrefix($processPrefix)
    {
        $this->processPrefix = $processPrefix;

        return $this;
    }
}
