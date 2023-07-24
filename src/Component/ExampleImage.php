<?php

namespace App\Component;

use GL\Math\Vec2;

class ExampleImage
{
    /**
     * The current sprite frame tick
     */
    public int $spriteFrameTick = 0;

    /**
     * The current sprite frame
     */
    public int $spriteFrame = 0;   

    /**
     * How many ticks to wait before switching to the next sprite frame
     */
    public int $spriteFrameRate = 10;

    /**
     * Speed
     */
    public Vec2 $speed;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->speed = new Vec2(0.0);
    }
}
