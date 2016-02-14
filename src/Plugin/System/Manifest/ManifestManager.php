<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\System\Manifest;

use Composer\Semver\Comparator;
use PHPSub\Plugin\System\Manifest\Exception\MalformedRemoteManifestException;
use PHPSub\Plugin\System\Manifest\Exception\ManifestFetchingException;
use PHPSub\Plugin\System\Manifest\Exception\RemoteManifestDownloadException;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class ManifestManager
{
    /**
     * @param string $manifestUrl
     */
    public function __construct($manifestUrl)
    {
        $this->manifestUrl = $manifestUrl;
    }

    /**
     * @param Manifest $manifest
     *
     * @return array|null
     */
    public function getLastVersion(Manifest $manifest)
    {
        $lastVersionEntry = null;

        foreach ($manifest->getEntries() as $entry) {
            if (null === $lastVersionEntry) {
                $lastVersionEntry = $entry;
                continue;
            }

            if (Comparator::greaterThan($entry['version'], $lastVersionEntry['version'])) {
                $lastVersionEntry = $entry;
            }
        }

        return $lastVersionEntry;
    }

    /**
     * @param Manifest $manifest
     * @param string   $version
     *
     * @return array|null
     */
    public function getEntryByVersion(Manifest $manifest, $version)
    {
        foreach ($manifest->getEntries() as $entry) {
            if (Comparator::equalTo($entry['version'], $version)) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @param Manifest $manifest
     * @param string   $version
     *
     * @return array|false
     */
    public function isNewVersionAvailable(Manifest $manifest, $version)
    {
        if (null === $lastVersion = $this->getLastVersion($manifest)) {
            return false;
        }

        if (Comparator::greaterThan($lastVersion['version'], $version)) {
            return $lastVersion;
        }

        return false;
    }

    /**
     * @param Manifest $manifest
     * @param string   $version
     *
     * @return array|false
     */
    public function checkIdenticalVersion(Manifest $manifest, $version)
    {
        if (null !== $entry = $this->getEntryByVersion($manifest, $version)) {
            return $entry;
        }

        return false;
    }

    /**
     * @param Manifest $manifest
     * @param string   $signature
     *
     * @return array|false
     */
    public function checkIdenticalSignature(Manifest $manifest, $signature)
    {
        foreach ($manifest->getEntries() as $entry) {
            if ($entry['sha1'] === $signature) {
                return $entry;
            }
        }

        return false;
    }

    /**
     * Load the remote manifest
     *
     * @return Manifest
     *
     * @throws ManifestFetchingException
     */
    public function loadManifest()
    {
        $rawManifestContent = @file_get_contents($this->manifestUrl);

        if (false === $rawManifestContent) {
            throw new RemoteManifestDownloadException($this->manifestUrl);
        }

        $manifestContent = json_decode($rawManifestContent, true);

        if (null === $manifestContent) {
            throw new MalformedRemoteManifestException($this->manifestUrl);
        }

        return $this->buildManifest($manifestContent);
    }

    /**
     * @param array $manifestEntries
     *
     * @return Manifest
     */
    public function buildManifest(array $manifestEntries)
    {
        $manifest = new Manifest();

        foreach ($manifestEntries as $entry) {
            $manifest->addEntry($entry);
        }

        return $manifest;
    }
}
