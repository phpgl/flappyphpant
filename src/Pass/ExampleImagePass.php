<?php

namespace App\Pass;

use App\Component\ExampleImage;
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

class ExampleImagePass extends RenderPass
{
    /**
     * Constructor
     *
     * @param array<ExampleImage> $exampleImages
     */
    public function __construct(
        private GLState $gl,
        private ShaderProgram $shader,
        private Texture $sprite,
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

        $this->sprite->bind(GL_TEXTURE0);

        glEnable(GL_BLEND);
        glBlendFunc(GL_SRC_ALPHA, GL_ONE_MINUS_SRC_ALPHA);
        glDisable(GL_DEPTH_TEST);
        glDisable(GL_CULL_FACE);

        // upload the instance data
        $buffer = new FloatBuffer();
        foreach ($this->entities->view(ExampleImage::class) as $entity => $exampleImage) {
            $transform = $this->entities->get($entity, Transform::class);
            $buffer->pushMat4($transform->getLocalMatrix());
            $buffer->push($exampleImage->spriteFrame);
        }

        $this->vertexArray->uploadInstanceData($buffer);

        // draw the vertex array
        $this->vertexArray->bind();
        $this->vertexArray->drawAll(GL_TRIANGLE_STRIP);
    }
}
