<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Distribution\Uploader\Adapters;

use Aws\S3\Exception\S3Exception;
use PHPSub\Plugin\System\Manifest\Manifest;
use Aws\S3\S3Client;
use PHPSub\Plugin\Distribution\Uploader\AbstractUploader;
use PHPSub\Plugin\Distribution\Uploader\Exception\UploadException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class S3Uploader extends AbstractUploader
{
    /** @var S3Client */
    private $s3;

    /**
     * {@inheritdoc}
     */
    public function __construct($config)
    {
        parent::__construct($config);

        $this->s3 = new S3Client($this->config['aws_config']);
    }

    /**
     * {@inheritdoc}
     */
    public function uploadPhar($archivePath, $targetFileName)
    {
        return $this->upload(fopen($archivePath, 'r'), $this->config['phar_base_path'] . '/' . $targetFileName);
    }

    /**
     * {@inheritdoc}
     */
    public function uploadManifest(Manifest $manifest, $targetFileName)
    {
        return $this->upload($manifest->dumpContent(), $this->config['manifest_base_path'] . '/' . $targetFileName);
    }

    /**
     * {@inheritdoc}
     */
    public function uploadPubKey($pubKeyFilePath, $targetFileName)
    {
        return $this->upload(fopen($pubKeyFilePath, 'r'), $this->config['phar_base_path'] . '/' . $targetFileName);
    }

    /**
     * @param string $content File content
     * @param string $path    Target file path
     *
     * @return string Object URL
     */
    private function upload($content, $path)
    {
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1, strlen($path) - 1);
        }

        try {
            $result = $this->s3->putObject(
                [
                    'Bucket' => $this->config['bucket'],
                    'Key'    => $path,
                    'Body'   => $content,
                    'ACL'    => 'public-read',
                ]
            );

            return $result->get('ObjectURL');
        } catch (S3Exception $e) {
            throw new UploadException($path, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['aws_config', 'bucket']);
        $resolver->setDefined(['manifest_base_path', 'phar_base_path']);

        $resolver->setAllowedTypes(
            [
                'aws_config'         => 'array',
                'bucket'             => 'string',
                'manifest_base_path' => 'string',
                'phar_base_path'     => 'string'
            ]
        );

        $resolver->setDefaults(['manifest_base_path' => '', 'phar_base_path' => '']);
        $resolver->setNormalizer(
            'aws_config',
            function (Options $options, $value) {
                if (!isset($value['version'])) {
                    $value['version'] = "2006-03-01";
                }

                return $value;
            }
        );
    }
}
