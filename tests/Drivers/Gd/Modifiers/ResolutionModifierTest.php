<?php

namespace Intervention\Image\Tests\Drivers\Gd\Modifiers;

use Intervention\Image\Drivers\Gd\Modifiers\ResolutionModifier;
use Intervention\Image\Tests\TestCase;
use Intervention\Image\Tests\Traits\CanCreateGdTestImage;

/**
 * @requires extension gd
 * @covers \Intervention\Image\Drivers\Gd\Modifiers\ResolutionModifier
 */
class ResolutionModifierTest extends TestCase
{
    use CanCreateGdTestImage;

    public function testResolutionChange(): void
    {
        $image = $this->createTestImage('test.jpg');
        $this->assertEquals(72.0, $image->resolution()->x());
        $this->assertEquals(72.0, $image->resolution()->y());
        $image->modify(new ResolutionModifier(1, 2));
        $this->assertEquals(1.0, $image->resolution()->x());
        $this->assertEquals(2.0, $image->resolution()->y());
    }
}
