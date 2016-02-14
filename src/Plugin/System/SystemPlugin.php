<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\System;

use PHPSub\Plugin\Plugin;
use PHPSub\Plugin\System\Command\ListAvailableVersionsCommand;
use PHPSub\Plugin\System\Command\SelfUpdateCommand;
use PHPSub\Plugin\System\Command\ShowVersionCommand;
use PHPSub\Plugin\System\Manifest\ManifestManager;
use PHPSub\Toolbelt;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class SystemPlugin extends Plugin
{
    /** @var ManifestManager */
    private $manifestManager;

    /**
     * {@inheritdoc}
     */
    public function registerCommands()
    {
        $commands = [
            new ListAvailableVersionsCommand(),
            new ShowVersionCommand(),
        ];

        if ($this->toolbelt->getEnv() === Toolbelt::ENV_DIST) {
            $commands[] = new SelfUpdateCommand();
        }

        return $commands;
    }

    /**
     * @return ManifestManager
     */
    public function getManifestManager()
    {
        if (null === $this->manifestManager) {
            $this->manifestManager = new ManifestManager(
                $this->configuration['manifest_url']
            );
        }

        return $this->manifestManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['manifest_url']);
        $resolver->setAllowedTypes(['manifest_url' => 'string']);
        $resolver->setAllowedValues(
            [
                'manifest_url' => function ($value) {
                    return false !== filter_var($value, FILTER_VALIDATE_URL);
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'system';
    }
}
