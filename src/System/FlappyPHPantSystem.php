<?php

namespace App\System;

use App\Component\SpriteComponent;
use App\Component\GlobalStateComponent;
use App\Component\PlayerComponent;
use GL\Math\{GLM, Quat, Vec2, Vec3};
use VISU\Component\DynamicTextLabelComponent;
use VISU\ECS\EntitiesInterface;
use VISU\ECS\SystemInterface;
use VISU\Geo\Transform;
use VISU\Graphics\Rendering\RenderContext;
use VISU\OS\InputContextMap;

class FlappyPHPantSystem implements SystemInterface
{   
    /**
     * The entity to hold the score label
     */
    private int $scoreLabelEntity;

    /**
     * The Current HighScore label
     */
    private int $highScoreLabelEntity;

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
        $entities->registerComponent(DynamicTextLabelComponent::class);

        $this->setupPlayerEntity($entities, $entities->create());
        $this->setupScoreLabel($entities);
    }

    /**
     * Create the requried components for the player entity
     */
    private function setupPlayerEntity(EntitiesInterface $entities, int $playerEntity) : void
    {
        // remove the components if they exist
        $entities->detachAll($playerEntity);

        $entities->attach($playerEntity, new SpriteComponent('visuphpant3.png'));
        $entities->attach($playerEntity, new PlayerComponent);
        $transform = $entities->attach($playerEntity, new Transform);
        $transform->position = new Vec3(0, 0, 0);
        $transform->scale = new Vec3(-7, 7, 1);
    }

    /**
     * Create the score label
     */
    private function setupScoreLabel(EntitiesInterface $entities) : void
    {
        $this->scoreLabelEntity = $entities->create();
        $entities->attach($this->scoreLabelEntity, new DynamicTextLabelComponent(
            text: '',
            isStatic: true,
        ));
        $transform = $entities->attach($this->scoreLabelEntity, new Transform);
        $transform->position = new Vec3(0, 40, 0);
        $transform->scale = new Vec3(0.5);

        $this->highScoreLabelEntity = $entities->create();
        $entities->attach($this->highScoreLabelEntity, new DynamicTextLabelComponent(
            text: '',
        ));
        $transform = $entities->attach($this->highScoreLabelEntity, new Transform);
        $transform->position = new Vec3(0, 30, 0);
        $transform->scale = new Vec3(0.5);

        // also create a "Press Space to Start" label
        $startLabelEntity = $entities->create();
        $entities->attach($startLabelEntity, new DynamicTextLabelComponent(
            text: 'Press Space to Start'
        ));
        $transform = $entities->attach($startLabelEntity, new Transform);
        $transform->position = new Vec3(0, -20, 0);
        $transform->scale = new Vec3(0.5);
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

        // update the score label
        $scoreLabel = $entities->get($this->scoreLabelEntity, DynamicTextLabelComponent::class);
        if ($gameState->score > 0) {
            $scoreLabel->text = 'Score: ' . $gameState->score;
        } else {
            $scoreLabel->text = '';
        }

        // update the highscore label
        $highScoreLabel = $entities->get($this->highScoreLabelEntity, DynamicTextLabelComponent::class);
        $highScoreLabel->text = 'High Score: ' . $gameState->highScore;

        // waiting for start 
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
        if (
            $this->inputContext->actions->didButtonPress('reset') || 
            (
                $playerComponent->dying && 
                $this->inputContext->actions->didButtonPress('jump') &&
                $playerComponent->speed < 0.5
            )
        ) {
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
