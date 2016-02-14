<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub\Plugin\Distribution\Uploader\Exception;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class UploadException extends \RuntimeException
{
    /** @var string */
    private $filename;

    /**
     * @param string          $filename
     * @param \Exception|null $previous
     */
    public function __construct($filename, \Exception $previous = null)
    {
        parent::__construct(sprintf("There was an error uploading `%s`", $filename), 0, $previous);

        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
