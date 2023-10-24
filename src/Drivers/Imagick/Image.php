<?php

namespace Intervention\Image\Drivers\Imagick;

use Imagick;
use ImagickException;
use Intervention\Image\Colors\Cmyk\Colorspace as CmykColorspace;
use Intervention\Image\Colors\Profile;
use Intervention\Image\Colors\Rgb\Colorspace as RgbColorspace;
use Intervention\Image\Drivers\Abstract\AbstractImage;
use Intervention\Image\Drivers\Imagick\Modifiers\ColorspaceModifier;
use Intervention\Image\Drivers\Imagick\Modifiers\ProfileModifier;
use Intervention\Image\Drivers\Imagick\Modifiers\ProfileRemovalModifier;
use Intervention\Image\Drivers\Imagick\Traits\CanHandleColors;
use Intervention\Image\Exceptions\ColorException;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ColorspaceInterface;
use Intervention\Image\Interfaces\FrameInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Iterator;

class Image extends AbstractImage implements ImageInterface, Iterator
{
    use CanHandleColors;

    protected $iteratorIndex = 0;

    public function __construct(protected Imagick $imagick)
    {
        //
    }

    public function getImagick(): Imagick
    {
        return $this->imagick;
    }

    public function getFrame(int $position = 0): ?FrameInterface
    {
        foreach ($this->imagick as $core) {
            if ($core->getIteratorIndex() == $position) {
                return new Frame($core);
            }
        }

        return null;
    }

    public function addFrame(FrameInterface $frame): ImageInterface
    {
        $imagick = $frame->getCore();

        $imagick->setImageDelay($frame->getDelay());
        $imagick->setImageDispose($frame->getDispose());

        $size = $frame->getSize();
        $imagick->setImagePage(
            $size->getWidth(),
            $size->getHeight(),
            $frame->getOffsetLeft(),
            $frame->getOffsetTop()
        );

        $this->imagick->addImage($imagick);

        return $this;
    }

    public function setLoops(int $count): ImageInterface
    {
        $this->imagick = $this->imagick->coalesceImages();
        $this->imagick->setImageIterations($count);

        return $this;
    }

    public function getLoops(): int
    {
        return $this->imagick->getImageIterations();
    }

    public function isAnimated(): bool
    {
        return $this->count() > 1;
    }

    public function count(): int
    {
        return $this->imagick->getNumberImages();
    }

    public function current(): mixed
    {
        $this->imagick->setIteratorIndex($this->iteratorIndex);

        return new Frame($this->imagick->current());
    }

    public function key(): mixed
    {
        return $this->iteratorIndex;
    }

    public function next(): void
    {
        $this->iteratorIndex = $this->iteratorIndex + 1;
    }

    public function rewind(): void
    {
        $this->iteratorIndex = 0;
    }

    public function valid(): bool
    {
        try {
            $result = $this->imagick->setIteratorIndex($this->iteratorIndex);
        } catch (ImagickException $e) {
            return false;
        }

        return $result;
    }

    public function getWidth(): int
    {
        return $this->getFrame()->getCore()->getImageWidth();
    }

    public function getHeight(): int
    {
        return $this->getFrame()->getCore()->getImageHeight();
    }

    public function pickColor(int $x, int $y, int $frame_key = 0): ?ColorInterface
    {
        if ($frame = $this->getFrame($frame_key)) {
            return $this->pixelToColor(
                $frame->getCore()->getImagePixelColor($x, $y),
                $this->getColorspace()
            );
        }

        return null;
    }

    public function getColorspace(): ColorspaceInterface
    {
        return match ($this->imagick->getImageColorspace()) {
            Imagick::COLORSPACE_CMYK => new CmykColorspace(),
            default => new RgbColorspace(),
        };
    }

    /**
     * {@inheritdoc}
     *
     * @see ImageInterface::setColorspace()
     */
    public function setColorspace(string|ColorspaceInterface $colorspace): ImageInterface
    {
        return $this->modify(new ColorspaceModifier($colorspace));
    }

    public function setProfile(string $filepath): ImageInterface
    {
        return $this->modify(
            new ProfileModifier(
                new Profile(file_get_contents($filepath))
            )
        );
    }

    public function getProfile(): Profile
    {
        $profiles = $this->imagick->getImageProfiles('icc');

        if (!array_key_exists('icc', $profiles)) {
            throw new ColorException('No ICC profile found.');
        }

        return new Profile($profiles['icc']);
    }

    public function withoutProfile(): ImageInterface
    {
        return $this->modify(new ProfileRemovalModifier());
    }
}
