<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\System\Manifest\Exception;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
abstract class ManifestFetchingException extends \RuntimeException
{
    /** @var string */
    private $manifestUrl;

    /**
     * @param string $message
     * @param string $manifestUrl
     */
    public function __construct($message, $manifestUrl)
    {
        parent::__construct($message);

        $this->manifestUrl = $manifestUrl;
    }

    /**
     * @return string
     */
    public function getManifestUrl()
    {
        return $this->manifestUrl;
    }
}
