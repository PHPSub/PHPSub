<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Distribution;

use PHPSub\Plugin\Distribution\Command\PublishArchiveCommand;
use PHPSub\Plugin\Distribution\Uploader\Adapters\S3Uploader;
use PHPSub\Plugin\Plugin;
use PHPSub\Plugin\System\Manifest\ManifestManager;
use PHPSub\Plugin\System\SystemPlugin;
use PHPSub\Toolbelt;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
final class DistributionPlugin extends Plugin
{
    /** @var DistributionManager */
    private $distributionManager;

    /**
     * {@inheritdoc}
     */
    public function registerCommands()
    {
        $commands = [];

        if ($this->toolbelt->getEnv() === Toolbelt::ENV_BUILD) {
            $commands[] = new PublishArchiveCommand();
        }

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['uploader', 'phar_path']);
        $resolver->setDefined(['manifest_file_name']);
        $resolver->setAllowedTypes(
            ['uploader'           => 'array',
             'manifest_file_name' => 'string',
             'phar_path'          => 'string'
            ]
        );
        $resolver->setAllowedValues(
            [
                'uploader' => function ($value) {
                    if (!isset($value['type'])) {
                        return false;
                    }

                    if (isset($value['config']) && !is_array($value['config'])) {
                        return false;
                    }

                    if (!in_array($value['type'], $this->getAvailableUploaders())) {
                        return false;
                    }
                    return true;
                }
            ]
        );

        $resolver->setNormalizer(
            'uploader',
            function (Options $options, $value) {
                $config = isset($value['config']) ? $value['config'] : [];
                return $this->buildUploader($value['type'], $config);
            }
        );

        $resolver->setNormalizer(
            'phar_path',
            function (Options $options, $value) {
                return $this->normalizePath($value);
            }
        );

        $resolver->setDefaults(['manifest_file_name' => 'manifest.json']);
    }

    /**
     * @return DistributionManager
     */
    public function getDistributionManager()
    {
        if (null === $this->distributionManager) {
            $this->distributionManager = new DistributionManager(
                $this->configuration['uploader'],
                $this->configuration['manifest_file_name']
            );
        }

        return $this->distributionManager;
    }

    /**
     * @return ManifestManager
     */
    public function getManifestManager()
    {
        /** @var SystemPlugin $systemPlugin */
        $systemPlugin = $this->toolbelt->getPlugin('system');

        return $systemPlugin->getManifestManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'distribution';
    }

    /**
     * @param string $type
     * @param array  $config
     *
     * @return null|S3Uploader
     */
    private function buildUploader($type, array $config)
    {
        switch ($type) {
            case 's3':
                return new S3Uploader($config);
        }

        return null;
    }

    /**
     * @return array
     */
    private function getAvailableUploaders()
    {
        return ['s3'];
    }
}
