<?php

namespace App\Pass;

use App\Component\SpriteComponent;
use GL\Buffer\FloatBuffer;
use VISU\ECS\EntitiesInterface;
use VISU\Geo\Transform;
use VISU\Graphics\BasicInstancedVertexArray;
use VISU\Graphics\GLState;
use VISU\Graphics\Rendering\Pass\CameraData;
use VISU\Graphics\Rendering\PipelineContainer;
use VISU\Graphics\Rendering\PipelineResources;
use VISU\Graphics\Rendering\RenderPass;
use VISU\Graphics\Rendering\RenderPipeline;
use VISU\Graphics\Rendering\Resource\RenderTargetResource;
use VISU\Graphics\ShaderProgram;
use VISU\Graphics\Texture;

class SpritePass extends RenderPass
{
    /**
     * Constructor
     *
     * @param array<SpriteComponent> $exampleImages
     * @param array<string, Texture> $spriteTextures
     * @param array<string, Vec2> $spriteDimensions
     */
    public function __construct(
        private GLState $gl,
        private ShaderProgram $shader,
        private array $spriteTextures,
        private array $spriteDimensions,
        private BasicInstancedVertexArray $vertexArray,
        private RenderTargetResource $renderTarget,
        private EntitiesInterface $entities,
    ) {
    }

    /**
     * Executes the render pass
     */
    public function setup(RenderPipeline $pipeline, PipelineContainer $data): void
    {
    }

    /**
     * Executes the render pass
     */
    public function execute(PipelineContainer $data, PipelineResources $resources): void
    {
        $resources->activateRenderTarget($this->renderTarget);

        $cameraData = $data->get(CameraData::class);

        $this->shader->use();

        $this->shader->setUniformMat4('u_view', false, $cameraData->view);
        $this->shader->setUniformMat4('u_projection', false, $cameraData->projection);
        $this->shader->setUniformVec2('u_resolution', $cameraData->getResolutionVec());
        $this->shader->setUniform1i('u_sprite', 0);

        // group the sprites by their sprite name
        $groupedSprites = [];
        foreach ($this->entities->view(SpriteComponent::class) as $entity => $sprite) {
            if (!isset($groupedSprites[$sprite->spriteName])) {
                $groupedSprites[$sprite->spriteName] = [];
            }
            $groupedSprites[$sprite->spriteName][] = $entity;
        }

        glEnable(GL_BLEND);
        glBlendFunc(GL_SRC_ALPHA, GL_ONE_MINUS_SRC_ALPHA);
        glDisable(GL_DEPTH_TEST);
        glDisable(GL_CULL_FACE);


        $this->vertexArray->bind();

        $buffer = new FloatBuffer();
        foreach($groupedSprites as $spriteName => $sprites) 
        {
            if (!isset($this->spriteTextures[$spriteName])) {
                throw new \Exception("Sprite texture not found: {$spriteName}");
            }

            $this->spriteTextures[$spriteName]->bind(GL_TEXTURE0);
            $this->shader->setUniform1i('u_rows', $this->spriteTextures[$spriteName]->width() / $this->spriteDimensions[$spriteName]->x);
            $this->shader->setUniform1i('u_cols', $this->spriteTextures[$spriteName]->height() / $this->spriteDimensions[$spriteName]->y);
            $buffer->clear();

            foreach ($sprites as $entity) 
            {
                $transform = $this->entities->get($entity, Transform::class);
                $sprite = $this->entities->get($entity, SpriteComponent::class);
                $buffer->pushMat4($transform->getLocalMatrix());
                $buffer->push($sprite->spriteFrame);
            }

            $this->vertexArray->uploadInstanceData($buffer);
            $this->vertexArray->drawAll(GL_TRIANGLE_STRIP);
        }
    }
}
