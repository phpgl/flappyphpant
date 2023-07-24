<?php

namespace App\System;

use App\Component\SpriteComponent;
use App\Component\GlobalStateComponent;
use App\Component\PlayerComponent;
use GL\Math\{GLM, Quat, Vec2, Vec3};
use VISU\ECS\EntitiesInterface;
use VISU\ECS\SystemInterface;
use VISU\Geo\Math;
use VISU\Geo\Transform;
use VISU\Graphics\Rendering\RenderContext;
use VISU\OS\InputContextMap;

class PipeSystem implements SystemInterface
{ 

    /**
     * Registers the system, this is where you should register all required components.
     * 
     * @return void 
     */
    public function register(EntitiesInterface $entities) : void
    {
        $entities->registerComponent(SpriteComponent::class);

        for ($i = 2; $i < 12; $i++) {
            $entity = $entities->create();
            $sprite = $entities->attach($entity, new SpriteComponent('pipe.png'));
            $transform = $entities->attach($entity, new Transform);
            $transform->position = new Vec3($i * 50, -45, 0);
            $transform->scale = new Vec3(10, 50, 1); 
        }

        for ($i = 2; $i < 12; $i++) {
            $entity = $entities->create();
            $sprite = $entities->attach($entity, new SpriteComponent('pipe.png'));
            $sprite->spriteFrame = 1;
            $transform = $entities->attach($entity, new Transform);
            $transform->position = new Vec3($i * 50, 0, 0);
            $transform->scale = new Vec3(10, 10, 1); 
        }
    }

    /**
     * Unregisters the system, this is where you can handle any cleanup.
     * 
     * @return void 
     */
    public function unregister(EntitiesInterface $entities) : void
    {
    }

    /**
     * Updates handler, this is where the game state should be updated.
     * 
     * @return void 
     */
    public function update(EntitiesInterface $entities) : void
    {
    }

    /**
     * Handles rendering of the scene, here you can attach additional render passes,
     * modify the render pipeline or customize rendering related data.
     * 
     * @param RenderContext $context
     */
    public function render(EntitiesInterface $entities, RenderContext $context) : void
    {
    }
}