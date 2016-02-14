<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Process\ParameterMapping;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class ProcessParameterMappingFactory
{
    /**
     * @param string $parameterName
     * @param string $type
     * @param array  $options
     *
     * @return null|ProcessArgumentMapping|ProcessOptionMapping
     */
    public function buildMapping($parameterName, $type, array $options = [])
    {
        if (!in_array($type, $this->getAvailableParameterMappingTypes(), true)) {
            throw new \LogicException(
                'Invalid mapping type. Available: ' . implode(', ', $this->getAvailableParameterMappingTypes())
            );
        }

        $filter = null;
        if (array_key_exists('filter', $options)) {
            if (null !== $options['filter'] && !is_callable($options['filter'])) {
                throw new \LogicException('The filter, if provided, must be a callable.');
            }

            $filter = $options['filter'];
        }

        $useShortcut = false;
        if (array_key_exists('useShortcut', $options)) {
            if ($type === ProcessArgumentMapping::getParameterType()) {
                throw new \LogicException('The argument mapping does not support `useShortcut` option.');
            } elseif ($type === ProcessOptionMapping::getParameterType()) {
                if (null !== $filter) {
                    throw new \LogicException(
                        'You can\'t combine a filter with the `useShortcut` option for option mappings'
                    );
                }

                if (!is_bool($options['useShortcut'])) {
                    throw new \LogicException(
                        'The `useShortcut` option must be a boolean'
                    );
                }

                $useShortcut = $options['useShortcut'];
            }
        }

        switch ($type) {
            case ProcessArgumentMapping::getParameterType():
                return new ProcessArgumentMapping($parameterName, $filter);
            case ProcessOptionMapping::getParameterType():
                return new ProcessOptionMapping($parameterName, $useShortcut, $filter);
        }

        return null;
    }

    /**
     * Gets the available parameter type the factory handle
     *
     * @return array
     */
    public function getAvailableParameterMappingTypes()
    {
        return [
            ProcessArgumentMapping::getParameterType(),
            ProcessOptionMapping::getParameterType()
        ];
    }
}
