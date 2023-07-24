<?php

namespace App\Scene;

use GameContainer;
use VISU\ECS\EntitiesInterface;
use VISU\ECS\EntityRegisty;
use VISU\ECS\SystemInterface;
use VISU\Graphics\Rendering\RenderContext;

abstract class BaseScene
{
    /**
     * The ECS registry, this is where your game state should be stored.
     */
    protected EntitiesInterface $entities;
    
    /**
     * Array of systems
     * 
     * @var array<SystemInterface>
     */
    protected array $systems = [];

    /**
     * Constructor
     *  
     * Dont load resources or bind event listeners here, use the `load` method instead.
     * We want to be able to load scenes without loading their resources. For example 
     * when preparing a scene to be switched or a loading screen.
     */
    public function __construct(
        protected GameContainer $container,
    )
    {
        $this->entities = new EntityRegisty();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->unregisterSystems();
    }
    
    /**
     * Returns the scenes name
     */
    abstract public function getName() : string;

    /**
     * Binds an array of systems to the scene
     * This is handy as you can register and unregister all thsese systems at once
     * 
     * Why do we not just call update and render on all systems automatically?
     * Because we want to be able to control the order in which systems are updated and rendered, 
     * in some cases we don't want to update all systems every frame.
     * This is really dependant on the game you are making. Which is why VISU doesn't come with a BaseScene class.
     * 
     * @param array<SystemInterface> $systems
     */
    protected function bindSystems(array $systems) : void
    {
        $this->systems = array_merge($this->systems, $systems);
    }
    
    /**
     * Registers all binded systems
     */
    public function registerSystems() : void
    {
        foreach ($this->systems as $system) {
            $system->register($this->entities);
        }
    }

    /**
     * Unregisters all binded systems
     */
    public function unregisterSystems() : void
    {
        foreach ($this->systems as $system) {
            $system->unregister($this->entities);
        }
    }

    /**
     * Loads resources required for the scene, prepere base entities 
     * basically prepare anything that is required to be ready before the scene is rendered.
     * 
     * @return void 
     */
    abstract public function load() : void;

    /**
     * Unloads resources required for the scene, cleanup base entities
     * 
     * @return void 
     */
    public function unload() : void 
    {
        $this->unregisterSystems();
    }

    /**
     * Updates the scene state
     * This is the place where you should update your game state.
     * 
     * @return void 
     */
    abstract public function update() : void;

    /**
     * Renders the scene
     * This is the place where you should render your game state.
     * 
     * @param RenderContext $context
     */
    abstract public function render(RenderContext $context) : void;
}
