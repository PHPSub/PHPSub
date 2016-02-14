<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Distribution\Uploader;

use PHPSub\Plugin\System\Manifest\Manifest;
use PHPSub\Plugin\Distribution\Uploader\Exception\UploadException;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
interface Uploader
{
    /**
     * Upload a PHAR archive
     *
     * @param string $archivePath    PHAR archive file path
     * @param string $targetFileName Name of the file to give on the remote platform
     *
     * @return string Phar URL
     *
     * @throws UploadException
     */
    public function uploadPhar($archivePath, $targetFileName);

    /**
     * Upload the manifest
     *
     * @param Manifest $manifest       Manifest object
     * @param string   $targetFileName Name of the file to give on the remote platform
     *
     * @return string Manifest URL
     *
     * @throws UploadException
     */
    public function uploadManifest(Manifest $manifest, $targetFileName);

    /**
     * Upload the public key
     *
     * @param string $pubKeyFilePath Public key file path
     * @param string $targetFileName Name of the file to give on the remote platform
     *
     * @return string PubKey URL
     *
     * @throws UploadException
     */
    public function uploadPubKey($pubKeyFilePath, $targetFileName);
}
