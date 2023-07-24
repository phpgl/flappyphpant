<?php

namespace App\System;

use App\Component\ExampleImage;
use App\Component\GlobalStateComponent;
use App\Component\PlayerComponent;
use GL\Math\{GLM, Quat, Vec2, Vec3};
use VISU\ECS\EntitiesInterface;
use VISU\ECS\SystemInterface;
use VISU\Geo\Math;
use VISU\Geo\Transform;
use VISU\Graphics\Rendering\RenderContext;
use VISU\OS\InputContextMap;

class VisuPhpantSystem implements SystemInterface
{   
    /**
     * Constructor
     */
    public function __construct(
        protected InputContextMap $inputContext,
    )
    {
    }

    /**
     * Registers the system, this is where you should register all required components.
     * 
     * @return void 
     */
    public function register(EntitiesInterface $entities) : void
    {
        $entities->registerComponent(ExampleImage::class);
        $entities->registerComponent(PlayerComponent::class);

        $entity = $entities->create();
        $spite = $entities->attach($entity, new ExampleImage);
        $player = $entities->attach($entity, new PlayerComponent);
        $transform = $entities->attach($entity, new Transform);
        $transform->position = new Vec3(0, 0, 0);
        $transform->scale = new Vec3(-7, 7, 1);
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
        $gameState = $entities->getSingleton(GlobalStateComponent::class);
        if ($gameState->waitingForStart) {

            if ($this->inputContext->actions->didButtonPress('jump')) {
                $gameState->waitingForStart = false;
                $gameState->paused = false;
            } else {
                return;
            }
        }

        $playerEntity = $entities->firstWith(PlayerComponent::class);
        $playerTransform = $entities->get($playerEntity, Transform::class);
        $playerTransform->position->x = $playerTransform->position->x + 1;
        $playerTransform->markDirty();
        $playerComponent = $entities->get($playerEntity, PlayerComponent::class);

        // count jump tick
        $playerComponent->jumpTick++;

        // apply jump
        if ($this->inputContext->actions->didButtonPress('jump')) {
            $playerComponent->velocity = $playerComponent->jumpForce;
            $playerComponent->jumpTick = 0;
        }

        // apply gravity
        $playerComponent->velocity -= $playerComponent->gravity;

        // apply velocity
        $playerTransform->position->x = $playerTransform->position->x + $playerComponent->speed;
        $playerTransform->position->y = $playerTransform->position->y + $playerComponent->velocity;

        // change the displayed sprite frame based on the jump tick
        $spriteComponent = $entities->get($playerEntity, ExampleImage::class);
        if ($playerComponent->jumpTick < 8) {
            $spriteComponent->spriteFrame = 2;
        } else if ($playerComponent->jumpTick < 15) {
            $spriteComponent->spriteFrame = 1;
        } else {
            $spriteComponent->spriteFrame = 0;
        }
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