<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin;

use PHPSub\Toolbelt;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
abstract class Plugin
{
    /** @var Toolbelt */
    protected $toolbelt;
    /** @var array */
    protected $configuration;

    /**
     * @param Toolbelt $toolbelt
     */
    public function setToolbelt(Toolbelt $toolbelt)
    {
        $this->toolbelt = $toolbelt;
    }

    /**
     * {@inheritdoc}
     */
    public function registerCommands()
    {
        return [];
    }

    /**
     * @param array $config
     */
    public function setConfiguration(array $config)
    {
        $resolver = new OptionsResolver();

        $this->configureOptions($resolver);

        $this->configuration = $resolver->resolve($config);
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function normalizePath($path)
    {
        return $this->toolbelt->normalizePath($path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getToolbeltRelativePath($path)
    {
        $fs = new Filesystem();

        $relativePath = $fs->makePathRelative($path, $this->toolbelt->getRootDir());

        if (substr($relativePath, strlen($relativePath) - 1, 1) === DIRECTORY_SEPARATOR) {
            $relativePath = substr($relativePath, 0, strlen($relativePath) - 1);
        }

        return $relativePath;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * @param Command[] $commands
     */
    public function configureExternalCommands(array $commands)
    {
    }

    /**
     * @return string
     */
    abstract public function getName();
}
