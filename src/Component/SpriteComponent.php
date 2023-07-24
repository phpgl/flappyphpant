<?php

namespace App\Component;

use GL\Math\Vec2;

class SpriteComponent
{
    /**
     * The current sprite frame
     */
    public int $spriteFrame = 0; 

    public function __construct(
        /**
         * The sprite name
         */
        public string $spriteName,
    )
    {
        
    }
}
