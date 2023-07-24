<?php

namespace App;

use GameContainer;

use App\Debug\DebugTextOverlay;
use App\Scene\BaseScene;
use App\Scene\GameViewScene;
use App\Signals\SwitchToSceneSignal;
use VISU\Graphics\Rendering\Pass\BackbufferData;
use VISU\Graphics\Rendering\Pass\ClearPass;
use VISU\Graphics\Rendering\PipelineContainer;
use VISU\Graphics\Rendering\PipelineResources;
use VISU\Graphics\Rendering\RenderContext;
use VISU\Graphics\Rendering\Renderer\Debug3DRenderer;
use VISU\Graphics\Rendering\RenderPipeline;
use VISU\Graphics\ShaderProgram;
use VISU\OS\InputContextMap;
use VISU\OS\Logger;
use VISU\OS\Window;
use VISU\Runtime\DebugConsole;
use VISU\Runtime\GameLoopDelegate;

class Game implements GameLoopDelegate
{
    /**
     * The container instance.
     * 
     * As this is the core of the game, we will use the container directly 
     * instead of injecting all dependencies into the constructor for convenience.
     */
    protected GameContainer $container;

    /**
     * Games window instance
     * This is the primary window which we will render the game into.
     */
    private Window $window;

    /**
     * Input context map allows you to map input events to actions
     * Allowing you to abstract the input from the actual input device.
     * 
     * This is a map because you can have multiple input action maps for 
     * different contexts in your app. For example you properly want to have
     * different input mappings for the game and the menu.
     */
    private InputContextMap $inputContext;

    /**
     * Current frame index
     * Every frame this value is incremented by one. The framerate and the update tick
     * run independently from each other. So the update tick might run at 60Hz while the
     * frame rate is 120Hz. This value allows you determine how many frames have passed.
     */
    private int $frameIndex = 0;

    /**
     * Debug font renderer
     */
    private DebugTextOverlay $dbgText;

    /**
     * Debug 3D renderer
     */
    private Debug3DRenderer $dbg3D;

    /**
     * Debug Console
     */
    private DebugConsole $dbgConsole;

    /**
     * Pipeline resource manager
     */
    private PipelineResources $pipelineResources;

    /**
     * The currently active scene
     */
    private BaseScene $currentScene;

    /**
     * The scene the game will switch to on the next frame
     */
    private ?BaseScene $nextScene = null;

    /**
     * Construct a new game instance
     */
    public function __construct(GameContainer $container)
    {
        $this->container = $container;
        $this->window = $container->resolveWindowMain();

        // initialize the window
        $this->window->initailize($this->container->resolveGL());

        // enable vsync by default
        $this->window->setSwapInterval(1);

        // make the input the windows event handler
        $this->window->setEventHandler($this->container->resolveInput());

        // initialize the input context map
        $this->inputContext = $this->container->resolveInputContext();

        // initialize the pipeline resources
        $this->pipelineResources = new PipelineResources($container->resolveGL());

        // preload all shaders
        $container->resolveShaders()->loadAll(function($name, ShaderProgram $shader) {
            Logger::info("(shader) loaded: {$name} -> {$shader->id}");
        });

        // initialize the debug renderers
        $this->dbgText = new DebugTextOverlay($container);
        $this->dbgConsole = new DebugConsole($container->resolveGL(), $container->resolveInput(), $container->resolveVisuDispatcher());
        $this->dbg3D = new Debug3DRenderer($container->resolveGL());
        Debug3DRenderer::setGlobalInstance($this->dbg3D);

        // load an inital scene
        $this->currentScene = new GameViewScene($container);

        // listen for scene switch signals
        $this->container->resolveVisuDispatcher()->register('scene.switch', function(SwitchToSceneSignal $signal) {
            $this->switchScene($signal->newScene);
        });
    }

    /**
     * Returns the current scene
     */
    public function getCurrentScene() : BaseScene
    {
        return $this->currentScene;
    }

    /**
     * Switches to the given scene
     */
    public function switchScene(BaseScene $scene) : void
    {
        $this->nextScene = $scene;
    }

    /**
     * Start the game
     * This will begin the game loop
     */
    public function start()
    {
        // initialize the current scene
        $this->currentScene->load();

        // start the game loop
        $this->container->resolveGameLoopMain()->start();
    }

    /**
     * Update the games state
     * This method might be called multiple times per frame, or not at all if
     * the frame rate is very high.
     * 
     * The update method should step the game forward in time, this is the place
     * where you would update the position of your game objects, check for collisions
     * and so on. 
     * 
     * @return void 
     */
    public function update() : void
    {
        // reset the input context for the next tick
        $this->inputContext->reset();

        // poll for new events
        $this->window->pollEvents();

        // update the current scene
        $this->currentScene->update();
    }

    /**
     * Render the current game state
     * This method is called once per frame.
     * 
     * The render method should draw the current game state to the screen. You recieve 
     * a delta time value which you can use to interpolate between the current and the
     * previous frame. This is useful for animations and other things that should be
     * smooth with variable frame rates.
     * 
     * @param float $deltaTime
     * @return void 
     */
    public function render(float $deltaTime) : void
    {
        $windowRenderTarget = $this->window->getRenderTarget();

        $data = new PipelineContainer;
        $pipeline = new RenderPipeline($this->pipelineResources, $data, $windowRenderTarget);
        $context = new RenderContext($pipeline, $data, $this->pipelineResources, $deltaTime);

        // backbuffer render target
        $backbuffer = $data->get(BackbufferData::class)->target;

        // render the current scene
        $this->currentScene->render($context);
        
        // render debug text
        $this->dbg3D->attachPass($pipeline, $backbuffer);
        $this->dbgText->attachPass($pipeline, $this->pipelineResources, $backbuffer, $deltaTime);
        $this->dbgConsole->attachPass($pipeline, $this->pipelineResources, $backbuffer);

        // execute the pipeline
        $pipeline->execute($this->frameIndex++, $this->container->resolveProfiler());

        // finalize the profiler
        $this->container->resolveProfiler()->finalize();

        $this->window->swapBuffers();

        // switch to the next scene if requested
        if ($this->nextScene) {
            Logger::info("Switching to scene: {$this->nextScene->getName()}");
            $this->currentScene->unload();
            $this->currentScene = $this->nextScene;
            $this->currentScene->load();
            $this->nextScene = null;
        }
    }

    /**
     * Loop should stop
     * This method is called once per frame and should return true if the game loop
     * should stop. This is useful if you want to quit the game after a certain amount
     * of time or if the player has lost all his lives etc..
     * 
     * @return bool 
     */
    public function shouldStop() : bool
    {
        return $this->window->shouldClose();
    }

    /**
     * Custom debug info.
     * I know, I know, there should't be references to game in the first place..
     */
    public function __debugInfo() {
		return ['currentScene' => $this->currentScene->getName()];
	}
}
