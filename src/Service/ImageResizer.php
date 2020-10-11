<?php

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use League\Flysystem\FilesystemInterface;


class ImageResizer
{
    private const MAX_WIDTH = 200;
    private const MAX_HEIGHT = 150;

    private $imagine;
    private $storage;

    public function __construct(FilesystemInterface $photosStorage)
    {
        $this->imagine = new Imagine();
        $this->storage = $photosStorage;
    }

    public function resize(string $filename): void
    {
        $stream = $this->storage->readStream($filename);
        $photo = $this->imagine->read($stream);
        
        $size = $photo->getSize();
        $ratio = $size->getWidth() / $size->getHeight();

        $width = self::MAX_WIDTH;
        $height = self::MAX_HEIGHT;
        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $this->storage->put($filename, 
            $photo->resize(new Box($width, $height))
                ->get(pathinfo($filename, \PATHINFO_EXTENSION)));
    }
}