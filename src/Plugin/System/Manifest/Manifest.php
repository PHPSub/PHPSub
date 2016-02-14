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

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class Manifest
{
    const SORT_VERSION_DESC = 'sort_version_desc';
    const SORT_VERSION_ASC = 'sort_version_asc';

    /** @var array */
    private $entries;

    /**
     * Manifest constructor.
     */
    public function __construct()
    {
        $this->entries = [];
    }

    /**
     * Adds an item into the manifest
     *
     * @param array $entry
     */
    public function addEntry(array $entry)
    {
        $this->entries[] = $entry;
    }

    /**
     * Dump the manifest content to JSON
     *
     * @return string The JSON manifest content
     */
    public function dumpContent()
    {
        return json_encode($this->entries, JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Gets entries sorted by version
     *
     * @param string $sort Sorting order
     *
     * @return array
     */
    public function getSortedEntries($sort = self::SORT_VERSION_DESC)
    {
        switch ($sort) {
            case self::SORT_VERSION_DESC:
                $sortMultiplier = -1;
                break;
            case self::SORT_VERSION_ASC:
                $sortMultiplier = 1;
                break;
            default:
                throw new \InvalidArgumentException('Invalid sort order: ' . $sort);
        }

        $entries = $this->entries;

        usort($entries, function ($a, $b) use ($sortMultiplier) {
            if (Comparator::compare($a['version'], '==', $b['version'])) {
                return 0;
            }

            return Comparator::compare($a['version'], '<', $b['version']) ? $sortMultiplier * -1 : $sortMultiplier * 1;
        });

        return $entries;
    }
}
