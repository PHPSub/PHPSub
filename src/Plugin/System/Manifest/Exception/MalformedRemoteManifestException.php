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
class MalformedRemoteManifestException extends ManifestFetchingException
{
    /**
     * @param string $manifestUrl
     */
    public function __construct($manifestUrl)
    {
        parent::__construct(sprintf("The remote manifest is not a correct JSON file `%s`", $manifestUrl), $manifestUrl);
    }
}
