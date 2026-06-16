<?php

namespace Keystone\Toolkit\Helpers;

class Image
{
    /**
     * Path to the image file
     *
     * @var string
     */
    public $path;

    /**
     * Width of the image
     *
     * @var int
     */
    public $width;

    /**
     * Height of the image
     *
     * @var int
     */
    public $height;

    /**
     * Image resource
     *
     * @var resource
     */
    public $source;

    /**
     * Aspect ratio of the image
     *
     * @var float
     */
    public $aspect;

    /**
     * MIME type of the image
     *
     * @var string
     */
    public $mimetype;

    /**
     * Original width of the image
     *
     * @var int
     */
    public $originalWidth;

    /**
     * Original height of the image
     *
     * @var int
     */
    public $originalHeight;

    /**
     * Creates an image object from a path
     *
     * @param string $path
     * @param int    $width
     * @param int    $height
     *
     * @return static
     */
    static public function fromPath(string $path, $width = null, $height = null)
    {
        return new self($path, $width, $height);
    }

    /**
     * Constructor
     *
     * @param string $source
     * @param int    $width
     * @param int    $height
     */
    public function __construct(string $source = null, $width = null, $height = null)
    {
        if (!empty($source)) {
            if (is_string($source)) {
                $this->path = $source;
                $this->source = $this->loadImage($this->path);
            }
            $this->setSource($this->source, $width, $height);
        }
    }

    /**
     * Load image based on its MIME type.
     *
     * @param string $path
     * @return resource
     */
    private function loadImage($path)
    {
        $info = getimagesize($path);
        $this->mimetype = $info['mime'];

        switch ($this->mimetype) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            default:
                throw new \Exception("Unsupported image format: {$this->mimetype}");
        }
    }

    /**
     * Sets the source image and its dimensions
     *
     * @param resource $source
     * @param int      $width
     * @param int      $height
     */
    private function setSource($source, $width = null, $height = null)
    {
        $this->width = !empty($width) ? $width : imagesx($source);
        $this->height = !empty($height) ? $height : imagesy($source);
        $this->aspect = $this->width / $this->height;

        $this->originalWidth = $this->width;
        $this->originalHeight = $this->height;
    }

    /**
     * Scales the image by a given factor
     *
     * @param float $factor
     *
     * @return static
     */
    public function scale($factor)
    {
        $this->width *= $factor;
        $this->height *= $factor;
        $newImage = imagecreatetruecolor($this->width, $this->height);
        imagecopyresampled($newImage, $this->source, 0, 0, 0, 0, $this->width, $this->height, $this->originalWidth, $this->originalHeight);
        $this->source = $newImage;
        return $this;
    }

    /**
     * Resizes the image to a given width and height
     *
     * @param int $width
     * @param int $height
     *
     * @return static
     */
    public function resize($width, $height)
    {
        if ($this->aspect >= 1) {
            $this->width = $width;
            $this->height = $width / $this->aspect;
        } else {
            $this->height = $height;
            $this->width = $height * $this->aspect;
        }
        $newImage = imagecreatetruecolor($this->width, $this->height);
        switch ($this->mimetype) {
            case 'image/jpeg':
                break;
            case 'image/png':
                $background = imagecolorallocate($newImage, 0, 0, 0);
                imagecolortransparent($newImage, $background);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case 'image/gif':
                break;
            default:
        }
        imagecopyresampled($newImage, $this->source, 0, 0, 0, 0, $this->width, $this->height, $this->originalWidth, $this->originalHeight);
        $this->source = $newImage;
        return $this;
    }

    /**
     * Resets the image to its original dimensions
     *
     * @return static
     */
    public function reset()
    {
        $this->width = $this->originalWidth;
        $this->height = $this->originalHeight;
        return $this;
    }

    /**
     * Writes the image to a file
     *
     * @param string $path
     *
     * @return static
     */
    public function write($path = null)
    {
        if (empty($path)) $path = $this->path;

        switch ($this->mimetype) {
            case 'image/jpeg':
                imagejpeg($this->source, $path);
                break;
            case 'image/png':
                imagepng($this->source, $path);
                break;
            case 'image/gif':
                imagegif($this->source, $path);
                break;
            default:
                throw new \Exception("Unsupported image format for saving: {$this->mimetype}");
        }
        return $this;
    }

    /**
     * Destructor to free up memory
     */
    public function __destruct()
    {
        if ($this->source) {
            imagedestroy($this->source);
        }
    }
}
