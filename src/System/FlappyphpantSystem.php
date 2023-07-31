<?php

namespace App\System;

use App\Component\SpriteComponent;
use App\Component\GlobalStateComponent;
use App\Component\PlayerComponent;
use GL\Math\{GLM, Quat, Vec2, Vec3};
use VISU\ECS\EntitiesInterface;
use VISU\ECS\SystemInterface;
use VISU\Geo\Transform;
use VISU\Graphics\Rendering\RenderContext;
use VISU\OS\InputContextMap;

class FlappyPHPantSystem implements SystemInterface
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
        $entities->registerComponent(SpriteComponent::class);
        $entities->registerComponent(PlayerComponent::class);

        $this->setupPlayerEntity($entities, $entities->create());
    }

    /**
     * Create the requried components for the player entity
     */
    private function setupPlayerEntity(EntitiesInterface $entities, int $playerEntity) : void
    {
        // remove the components if they exist
        $entities->detachAll($playerEntity);

        $entities->attach($playerEntity, new SpriteComponent('visuphpant2.png'));
        $entities->attach($playerEntity, new PlayerComponent);
        $transform = $entities->attach($playerEntity, new Transform);
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
        $playerComponent = $entities->get($playerEntity, PlayerComponent::class);
        $playerTransform = $entities->get($playerEntity, Transform::class);
        $playerTransform->storePrevious();

        // reset the game
        if ($this->inputContext->actions->didButtonPress('reset')) {
            $gameState->paused = true;
            $gameState->waitingForStart = true;
            $gameState->tick = 0;
            // reset player
            $this->setupPlayerEntity($entities, $playerEntity);
            return;
        }

        if ($gameState->paused) {
            return;
        }

        // update the player during play
        if ($playerComponent->dying) {
            $this->updateDuringDeath($entities);
        } else {
            $this->updateDuringPlay($entities);
        }
    }

    /**
     * Update the player movement during play
     */
    public function updateDuringPlay(EntitiesInterface $entities) : void
    {
        $playerEntity = $entities->firstWith(PlayerComponent::class);
        $playerComponent = $entities->get($playerEntity, PlayerComponent::class);
        $playerTransform = $entities->get($playerEntity, Transform::class);

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
        $playerComponent->position->x = $playerComponent->position->x + $playerComponent->speed;
        $playerComponent->position->y = $playerComponent->position->y + $playerComponent->velocity;
        
        // copy the player position to the transform
        $playerTransform->position->x = $playerComponent->position->x;
        $playerTransform->position->y = $playerComponent->position->y;

        // change the displayed sprite frame based on the jump tick
        $spriteComponent = $entities->get($playerEntity, SpriteComponent::class);
        if ($playerComponent->jumpTick < 8) {
            $spriteComponent->spriteFrame = 2;
        } else if ($playerComponent->jumpTick < 15) {
            $spriteComponent->spriteFrame = 1;
        } else {
            $spriteComponent->spriteFrame = 0;
        }
    }

    /**
     * Update the player movement during death
     */
    public function updateDuringDeath(EntitiesInterface $entities) : void
    {
        $playerEntity = $entities->firstWith(PlayerComponent::class);
        $playerComponent = $entities->get($playerEntity, PlayerComponent::class);
        $playerTransform = $entities->get($playerEntity, Transform::class);
        $spriteComponent = $entities->get($playerEntity, SpriteComponent::class);
        $spriteComponent->spriteFrame = 2;

        $playerComponent->speed = $playerComponent->speed * 0.99;

        $playerTransform->position->x = $playerTransform->position->x - $playerComponent->speed;
        $playerTransform->orientation->rotate($playerComponent->speed / 5, new Vec3(0, 0, 1));

        // apply gravity
        $playerComponent->velocity -= $playerComponent->gravity;

        // dampen velocity
        $playerComponent->velocity = $playerComponent->velocity * 0.97;

        // apply velocity
        $playerTransform->position->y = $playerTransform->position->y + $playerComponent->velocity;

        // floor
        if ($playerTransform->position->y < -45) {
            $playerTransform->position->y = -45;
            $playerComponent->velocity = $playerComponent->velocity * -1;
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