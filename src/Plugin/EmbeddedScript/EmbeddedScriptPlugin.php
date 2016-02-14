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

use PHPSub\Plugin\EmbeddedScript\Command\DumpScriptsCommand;
use PHPSub\Plugin\Plugin;
use PHPSub\Toolbelt;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
final class EmbeddedScriptPlugin extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function registerCommands()
    {
        return [
            new DumpScriptsCommand()
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['dump_folder']);

        $resolver->setAllowedTypes(['dump_folder' => 'string']
        );

        $resolver->setAllowedValues(
            [
                'dump_folder' => function ($value) {
                    return is_dir(dirname($value)) && is_writable(dirname($value));
                },
            ]
        );

        $resolver->setDefault('dump_folder', function (Options $options) {
            return $this->toolbelt->getHomeDir() . DIRECTORY_SEPARATOR . 'scripts';
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureExternalCommands(array $commands)
    {
        foreach ($commands as $command) {
            if ($command instanceof EmbeddedScriptCommand) {
                $command->setEmbeddedScriptPlugin($this);
            }
        }
    }

    /**
     * Dump script folder to filesystem
     */
    public function dumpScripts()
    {
        $source = null;
        if ($this->toolbelt->getEnv() === Toolbelt::ENV_BUILD) {
            $archivePlugin = $this->toolbelt->getPlugin('archive');
            $source        = $archivePlugin->getConfiguration()['scripts_dir'];
        } elseif ($this->toolbelt->getEnv() === Toolbelt::ENV_DIST) {
            $source = $this->toolbelt->getRootDir() . DIRECTORY_SEPARATOR . '../artifacts/scripts';
        }

        $target = $this->getConfiguration()['dump_folder'];


        $fs = new Filesystem();
        $fs->remove($target);
        $fs->mirror($source, $target);
        $fs->chmod($target, 0755, 0000, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'embedded_script';
    }
}
