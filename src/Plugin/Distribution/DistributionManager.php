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

use Composer\Semver\VersionParser;
use PHPSub\Plugin\System\Manifest\Manifest;
use PHPSub\Plugin\Distribution\Uploader\Uploader;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class DistributionManager
{
    /** @var Uploader */
    private $uploader;
    /** @var string */
    private $manifestFileName;

    /**
     * @param Uploader $uploader
     * @param string   $manifestFileName
     */
    public function __construct(Uploader $uploader, $manifestFileName)
    {
        $this->uploader         = $uploader;
        $this->manifestFileName = $manifestFileName;
    }

    /**
     * Publish an archive remotely and update remote manifest
     *
     * @param string   $version
     * @param string   $pharPath
     * @param Manifest $manifest
     *
     * @return array New manifest entry
     */
    public function distributeArchive($version, $pharPath, Manifest $manifest)
    {
        $semver = new VersionParser();
        $semver->normalize($version);

        $fileNames = $this->generateFileNames($version, $pharPath);

        $pubKeyFile = null;
        if (file_exists($pharPath . '.pubkey')) {
            $pubKeyFile = $pharPath . '.pubkey';
        }

        $pharUrl = $this->uploader->uploadPhar($pharPath, $fileNames['phar']);
        $this->uploader->uploadPhar($pharPath, $fileNames['phar_latest']);
        $pubKeyUrl = null;
        if ($pubKeyFile) {
            $pubKeyUrl = $this->uploader->uploadPubKey($pubKeyFile, $fileNames['pubkey']);
        }

        $manifestEntry = [
            'name'    => $fileNames['original_phar'],
            'sha1'    => $this->getArchiveSignature($pharPath),
            'url'     => $pharUrl,
            'version' => $version
        ];

        if ($pubKeyFile) {
            $manifestEntry['publicKey'] = $pubKeyUrl;
        }

        $manifest->addEntry($manifestEntry);

        $this->uploader->uploadManifest($manifest, $this->manifestFileName);

        return $manifestEntry;
    }

    /**
     * Extract the Phar signature
     *
     * @param string $pharPath
     *
     * @return string
     */
    public function getArchiveSignature($pharPath)
    {
        $phar = new \Phar($pharPath);

        return strtolower($phar->getSignature()['hash']);
    }

    /**
     * Generate target file names based on version
     *
     * @param string $version
     * @param string $pharPath
     *
     * @return array
     */
    private function generateFileNames($version, $pharPath)
    {
        $pathInfo = pathinfo($pharPath);

        $pharBaseName = $pathInfo['filename'];
        $pharName     = $pharBaseName . '-' . $version . '.' . $pathInfo['extension'];

        $fileNames = [
            'original_phar' => $pathInfo['basename'],
            'phar'          => $pharName,
            'phar_latest'   => $pharBaseName . '-latest.' . $pathInfo['extension'],
            'pubkey'        => $pharName . '.pubkey'
        ];

        return $fileNames;
    }
}
