<?php

namespace chocochip\uploadimage;

use Storage;

class UploadImage
{
    /**
     * Instance of the input image file.
     *
     * @var object
     */
    protected $image;

    /**
     * Instance of the stored image name.
     *
     * @var string
     */
    protected $filename;

    /**
     * Instance of the stored resized image name.
     *
     * @var string
     */
    protected $resized_filename;

    /**
     * Instance of the input image file size.
     *
     * @var int
     */
    protected $size;

    /**
     * Instance of the input image file extension.
     *
     * @var string
     */
    protected $extension;

    /**
     * Instance of the path to store image inside storage/app/public.
     *
     * @var string
     */
    protected $path;

    /**
     * Instance of the full path to store image inside storage/app/public.
     *
     * @var string
     */
    protected $fullpath;

    /**
     * Instance of the image's max-width and max-height to resize to.
     *
     * @var int
     */
    protected $max_size;

    /**
     * Instance of the resized size.
     *
     * @var array
     */
    protected $new_size;

    public function __construct()
    {
        $this->new_size = array('width' => '', 'height' => '');
    }

    public function store($input, $path, $max_size = 300)
    {
        $this->image = $input;
        $this->size = getimagesize($this->image);
        $this->extension = $this->image->extension();
        $this->filename = time() . "_" . rand(0, 999999999) . "." . $this->extension;
        $this->path = $path;
        $this->fullpath = storage_path('app/public/' . $this->path);
        $this->max_size = $max_size;
        $this->checkDir();
        $this->handleUploadAndResize();
        return $this;
    }

    public function getStoredFileName()
    {
        return $this->filename;
    }

    public function getStoredResizedFileName()
    {
        return $this->resized_filename;
    }

    public function getResizedSize()
    {
        return $this->new_size;
    }

    public function getMimeType()
    {
        return $this->image->getMimeType();
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function getFullPath()
    {
        return $this->fullpath;
    }

    /**
     * Delete a single image
     *
     * @param string $filename
     * @param string $path
     */
    public function delete($filename, $path)
    {
        $path = '/public/' . $path;
        Storage::delete([$path . '/' . $filename, $path . '/resized-' . $filename]);
        return $this;
    }

    protected function handleUploadAndResize()
    {
        $this->handleResize();
        $this->handleUpload();
        return $this->filename;
    }

    protected function handleUpload()
    {

        $this->image->storeAs('public/' . $this->path, $this->filename);
        return $this->filename;
    }

    protected function handleResize()
    {
        $destination_image = $this->createBlankImage($this->max_size);

        $this->saveResizedImage($destination_image);

        return $this->resized_filename = 'resized-' . $this->filename;
    }

    protected function saveResizedImage($destination_image)
    {
        $path = $this->fullpath . '/resized-' . $this->filename;

        switch ($this->extension) {
            case 'jpeg':
                $orig_image = imagecreatefromjpeg($this->image);
                $this->copyResampled($destination_image, $orig_image);
                imagejpeg($destination_image, $path);
                break;

            case 'jpg':
                $orig_image = imagecreatefromjpeg($this->image);
                $this->copyResampled($destination_image, $orig_image);
                imagejpeg($destination_image, $path);
                break;

            case 'png':
                $orig_image = imagecreatefrompng($this->image);
                $this->copyResampled($destination_image, $orig_image);
                imagepng($destination_image, $path);
                break;

            case 'gif':
                $orig_image = imagecreatefromgif($this->image);
                $this->copyResampled($destination_image, $orig_image);
                imagegif($destination_image, $path);
                break;
        }
    }

    protected function copyResampled($destination_image, $orig_image)
    {
        return imagecopyresampled($destination_image, $orig_image, 0, 0, 0, 0, $this->new_size['width'], $this->new_size['height'], $this->size[0], $this->size[1]);
    }

    protected function createBlankImage($max_size)
    {
        if ($this->isPortrait()) {
            $this->new_size['height'] = $max_size;
            $this->new_size['width'] = $this->new_size['height'] * $this->getRatio();
        } else {
            $this->new_size['width'] = $max_size;
            $this->new_size['height'] = $this->new_size['width'] / $this->getRatio();
        }
        return imagecreatetruecolor($this->new_size['width'], $this->new_size['height']);
    }

    protected function isPortrait()
    {
        return ($this->getRatio() <= 1);
    }

    protected function getRatio()
    {
        return $this->size[0] / $this->size[1];
    }

    protected function checkDir()
    {
        if (!is_dir(storage_path('app/public/' . $this->path))) mkdir(storage_path('app/public/' . $this->path));

    }
}