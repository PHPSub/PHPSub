<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Archive;

use PHPSub\Plugin\Archive\Command\BuildArchiveCommand;
use PHPSub\Plugin\Plugin;
use PHPSub\Toolbelt;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
final class ArchivePlugin extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function registerCommands()
    {
        $commands = [];

        if ($this->toolbelt->getEnv() === Toolbelt::ENV_BUILD) {
            $commands[] = new BuildArchiveCommand();
        }

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['scripts_dir', 'build_dir', 'box_build_config']);
        $resolver->setAllowedTypes([
            'scripts_dir' => 'string',
            'build_dir' => 'string',
            'box_build_config' => 'array',
        ]);
        $resolver->setAllowedValues(
            [
                'scripts_dir' => function ($value) {
                    return is_dir($this->toolbelt->normalizePath($value));
                },
                'build_dir' => function ($value) {
                    $path = $this->toolbelt->normalizePath($value);
                    return is_dir(dirname($path)) && is_writable(dirname($path));
                },
            ]
        );

        $resolver->setNormalizer('scripts_dir', function (Options $options, $value) {
            return $this->toolbelt->normalizePath($value);
        });
        $resolver->setNormalizer('build_dir', function (Options $options, $value) {
            return $this->toolbelt->normalizePath($value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'archive';
    }
}

