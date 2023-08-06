<?php

namespace App\System;

use App\Component\SpriteComponent;
use App\Component\GlobalStateComponent;
use App\Component\PlayerComponent;
use App\Debug\DebugTextOverlay;
use GL\Math\{GLM, Quat, Vec2, Vec3};
use VISU\D3D;
use VISU\ECS\EntitiesInterface;
use VISU\ECS\SystemInterface;
use VISU\Geo\AABB2D;
use VISU\Geo\Transform;
use VISU\Graphics\Rendering\Pass\CameraData;
use VISU\Graphics\Rendering\RenderContext;
use VISU\OS\Logger;

class PipeSystem implements SystemInterface
{ 
    /**
     * This values is used to determine how many 
     * pipes we want to render on the screen.
     */
    private float $viewportWidth = 250;

    /**
     * The height of the viewport, as we have a orthographicStaticWorld 
     * this value is never going to change.
     */
    private float $viewportHeight = 50;

    /**
     * The distance between each pipe.
     */
    private float $pipeDistance = 70;

    /**
     * Start offfset before the first pipe.
     */
    private float $pipeStartOffset = 100;

    /**
     * The pipes size relative to the viewport size
     */
    private float $pipeSize = 10;

    /**
     * The predetermined pipe heights
     * They can range between -45 and 45
     * 
     * @var array<float>
     */
    private array $pipeHeights = [];

    /**
     * The predetermined pipe gabs 
     * This determines how large the gab is where the player can fly through
     */
    private array $pipeGabs = [];

    /**
     * An array of entities designed to hold the pipes
     * 
     * @var array<array{
     *   pipeTop: int,
     *   pipeBottom: int,
     *   outletTop: int,
     *   outletBottom: int,
     * }>
     */
    private array $pipeEntities = [];

    /**
     * Registers the system, this is where you should register all required components.
     * 
     * @return void 
     */
    public function register(EntitiesInterface $entities) : void
    {
        $entities->registerComponent(SpriteComponent::class);
        $entities->registerComponent(PlayerComponent::class);

        // precaclulate the level by generating the pipe heights
        mt_srand(42);
        for($i = 0; $i < 1000; $i++) {
            $height = mt_rand(-30, 30);
            $this->pipeHeights[] = $height;

            // the gab should be smaller based on the distance traveled
            $this->pipeGabs[] = max(50.0 - $i * 0.05, 15.0);
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
     * Will create and or remove entities to ensure that we have the correct amount of pipes
     */
    private function ensurePipeEntities(EntitiesInterface $entities, int $count) : void
    {
        if (count($this->pipeEntities) < $count) {
            for ($i = count($this->pipeEntities); $i < $count; $i++) {

                // pipes
                $pipeTopEntity = $entities->create();
                $entities->attach($pipeTopEntity, new Transform);
                $pipeTopSprite = $entities->attach($pipeTopEntity, new SpriteComponent('pipe.png'));
                $pipeTopSprite->spriteFrame = 0; // the first sprite frame

                $pipeBottomEntity = $entities->create();
                $entities->attach($pipeBottomEntity, new Transform);
                $pipeBottomSprite = $entities->attach($pipeBottomEntity, new SpriteComponent('pipe.png'));
                $pipeBottomSprite->spriteFrame = 0;

                // outlets
                $outletTopEntity = $entities->create();
                $entities->attach($outletTopEntity, new Transform);
                $outletTopSprite = $entities->attach($outletTopEntity, new SpriteComponent('pipe.png'));
                $outletTopSprite->spriteFrame = 1; // the second sprite frame

                $outletBottomEntity = $entities->create();
                $entities->attach($outletBottomEntity, new Transform);
                $outletBottomSprite = $entities->attach($outletBottomEntity, new SpriteComponent('pipe.png'));
                $outletBottomSprite->spriteFrame = 1;

                $this->pipeEntities[] = [
                    'pipeTop' => $pipeTopEntity,
                    'pipeBottom' => $pipeBottomEntity,
                    'outletTop' => $outletTopEntity,
                    'outletBottom' => $outletBottomEntity,
                ];
            }
        }

        elseif (count($this->pipeEntities) > $count) {
            for ($i = count($this->pipeEntities) - 1; $i >= $count; $i--) {
                $entities->destroy($this->pipeEntities[$i]['pipeTop']);
                $entities->destroy($this->pipeEntities[$i]['pipeBottom']);
                $entities->destroy($this->pipeEntities[$i]['outletTop']);
                $entities->destroy($this->pipeEntities[$i]['outletBottom']);
                unset($this->pipeEntities[$i]);
            }
        }
    }

    /**
     * Returns the number of pipes required to fill the viewport
     */
    public function getPipeCount() : int
    {
        return (int) ceil($this->viewportWidth / $this->pipeDistance);
    }

    /**
     * Updates handler, this is where the game state should be updated.
     * 
     * @return void 
     */
    public function update(EntitiesInterface $entities) : void
    {
        $playerEntity = $entities->firstWith(PlayerComponent::class);
        $playerTransform = $entities->get($playerEntity, Transform::class);
        $playerComponent = $entities->get($playerEntity, PlayerComponent::class);

        $traveledDistance = $playerTransform->position->x;

        // calculate the current pipe index
        $pipeCount = $this->getPipeCount();

        // calculate the current pipe index this is equal to the score of the player
        $centerPipeIndex = (int) floor(max(0, ($traveledDistance - $this->pipeStartOffset + $this->pipeDistance) / $this->pipeDistance));

        // update the games score based on it
        $globalState = $entities->getSingleton(GlobalStateComponent::class);
        $globalState->score = $centerPipeIndex;
        $globalState->highScore = max($globalState->highScore, $globalState->score);

        // now our starting pipe is always the center pipe - half the pipe count
        $pipeIndex = (int) max(0, $centerPipeIndex - (int) floor($pipeCount / 2));

        // we also generate the collison aabbs here
        // this is super waste full but we have tons of cpu time to spare
        $aabbs = [];
        // we build the AABBB on the basis of the sprite and then use our 
        // transform to transform it into world space
        $spriteAABB = new AABB2D(
            new Vec2(-1, -1),
            new Vec2(1, 1)
        );

        // translate the existing pipes to their intended positions
        $it = 0;
        foreach($this->pipeEntities as $pipeGroup) 
        {
            $pipeTopTransform = $entities->get($pipeGroup['pipeTop'], Transform::class);
            $pipeBottomTransform = $entities->get($pipeGroup['pipeBottom'], Transform::class);
            $outletTopTransform = $entities->get($pipeGroup['outletTop'], Transform::class);
            $outletBottomTransform = $entities->get($pipeGroup['outletBottom'], Transform::class);

            $renderPipeIndex = $pipeIndex + $it;

            $height = $this->pipeHeights[$renderPipeIndex];
            $gabSize = $this->pipeGabs[$renderPipeIndex];

            // first we want to transform the outlets 
            // the bottom outlets top position should be at gab size distance from the height
            $bottomY = $height - $gabSize / 2;
            $bottomY -= $this->pipeSize;

            $topY = $height + $gabSize / 2;
            $topY += $this->pipeSize;

            $outletBottomTransform->position = new Vec3($renderPipeIndex * $this->pipeDistance + $this->pipeStartOffset, $bottomY, 0);
            $outletBottomTransform->scale = new Vec3($this->pipeSize); 
            $outletBottomTransform->markDirty();

            $outletTopTransform->position = new Vec3($renderPipeIndex * $this->pipeDistance + $this->pipeStartOffset, $topY, 0);
            $outletTopTransform->scale = new Vec3($this->pipeSize, -$this->pipeSize, 1);
            $outletTopTransform->markDirty();

            $pipeBottomTransform->position = new Vec3($renderPipeIndex * $this->pipeDistance + $this->pipeStartOffset, $bottomY - $this->viewportHeight, 0);
            $pipeBottomTransform->scale = new Vec3($this->pipeSize, $this->viewportHeight, 1);
            $pipeBottomTransform->markDirty();

            $pipeTopTransform->position = new Vec3($renderPipeIndex * $this->pipeDistance + $this->pipeStartOffset, $topY + $this->viewportHeight, 0);
            $pipeTopTransform->scale = new Vec3($this->pipeSize, -$this->viewportHeight, 1);
            $pipeTopTransform->markDirty();


            // construct the aabb
            $outpletTopAABB = $spriteAABB->copy();
            $outpletTopAABB->applyTransform($outletTopTransform);
            $pipeTopAABB = $spriteAABB->copy();
            $pipeTopAABB->applyTransform($pipeTopTransform);
            $topAABB = AABB2D::union($outpletTopAABB, $pipeTopAABB);

            $outletBottomAABB = $spriteAABB->copy();
            $outletBottomAABB->applyTransform($outletBottomTransform);
            $pipeBottomAABB = $spriteAABB->copy();
            $pipeBottomAABB->applyTransform($pipeBottomTransform);
            $bottomAABB = AABB2D::union($outletBottomAABB, $pipeBottomAABB);

            // D3D::aabb2D(
            //     new Vec2(),
            //     $topAABB->min,
            //     $topAABB->max,
            //     D3D::$colorRed
            // );

            // D3D::aabb2D(
            //     new Vec2(),
            //     $bottomAABB->min,
            //     $bottomAABB->max,
            //     D3D::$colorGreen
            // );

            $aabbs[] = $topAABB;
            $aabbs[] = $bottomAABB;
        
            $it++;
        }

        if ($playerComponent->dying) {
            return;
        }

        // construct the player aabb
        // for the player we are going to be a bit forgiving 
        // and scale the abb a bit down
        $playerAABB = $spriteAABB->copy(); 
        $playerAABB->applyTransform($playerTransform);
        $playerAABB->min->x = $playerAABB->min->x + 2.5;
        $playerAABB->max->x = $playerAABB->max->x - 2.5;
        $playerAABB->min->y = $playerAABB->min->y + 2.8;
        $playerAABB->max->y = $playerAABB->max->y - 4.0;
        
        // D3D::aabb2D(
        //     new Vec2(),
        //     $playerAABB->min,
        //     $playerAABB->max,
        //     D3D::$colorGreen
        // );

        // check if the player is colliding with any of the pipes
        foreach($aabbs as $aabb) {
            if ($aabb->intersects($playerAABB)) {
                Logger::info('Player collided with pipe');
                $playerComponent->velocity = -1;
                $playerComponent->dying = true;
            }
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
        // update the viewport width
        $cameraData = $context->data->get(CameraData::class);
        $this->viewportWidth = $cameraData->viewport->getWidth() * 1.4; // we want some overlap

        // update the number of pipes
        $this->ensurePipeEntities($entities, $this->getPipeCount());

        DebugTextOverlay::debugString('pipe count: ' . $this->getPipeCount());
    }
}