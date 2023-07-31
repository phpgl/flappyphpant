<?php

namespace App\Component;

use GL\Math\Vec2;

class PlayerComponent
{
    /**
     * Players y velocity
     */
    public float $velocity = 0.0;

    /**
     * Players speed
     */
    public float $speed = 1.2;

    /**
     * Players jump force
     */
    public float $jumpForce = 2.0;

    /**
     * Gravity
     */
    public float $gravity = 0.1;

    /**
     * Tick counter since last jump
     * Used for animation
     */
    public int $jumpTick = 0;

    /**
     * The players position
     */
    public Vec2 $position;

    /**
     * Boolean if the player is currently dying 
     */
    public bool $dying = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->position = new Vec2(0, 0);
    }
}
