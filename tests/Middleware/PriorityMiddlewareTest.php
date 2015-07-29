<?php

namespace ConnectHolland\Tactician\PriorityPlugin\tests\Middleware;

use ConnectHolland\Tactician\PriorityPlugin\EventDispatcher\SymfonyEventDispatcher;
use ConnectHolland\Tactician\PriorityPlugin\Middleware\PriorityMiddleware;
use ConnectHolland\Tactician\PriorityPlugin\Tests\Fixtures\Command\FreeCommand;
use ConnectHolland\Tactician\PriorityPlugin\Tests\Fixtures\Command\RequestCommand;
use ConnectHolland\Tactician\PriorityPlugin\Tests\Fixtures\Command\SecondSequenceCommand;
use ConnectHolland\Tactician\PriorityPlugin\Tests\Fixtures\Command\SequenceCommand;
use ConnectHolland\Tactician\PriorityPlugin\Tests\Fixtures\Command\UrgentCommand;
use ConnectHolland\Tactician\PriorityPlugin\Tests\Fixtures\Messaging\InMemoryMessaging;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector;
use League\Tactician\Tests\Fixtures\Command\AddTaskCommand;
use League\Tactician\Tests\Fixtures\Handler\DynamicMethodsHandler;
use PHPUnit_Framework_TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Unit test for the priority middleware.
 *
 * @author ron
 */
class PriorityMiddlewareTest extends PHPUnit_Framework_TestCase
{
    /**
     * PriorityMiddleware instance to test.
     */
    private $priorityMiddleware;

    /**
     * Command Bus for testing.
     */
    private $commandBus;

    /**
     * DynamicMethodsHander.
     */
    private $methodHandler;

    /**
     * Creates a command bus to use for testing.
     */
    public function setUp()
    {
        $this->methodHandler = new DynamicMethodsHandler();
        $handlerMiddleware = new CommandHandlerMiddleware(
            new ClassNameExtractor(),
            new InMemoryLocator([
                AddTaskCommand::class => $this->methodHandler,
                UrgentCommand::class => $this->methodHandler,
                SequenceCommand::class => $this->methodHandler,
                SecondSequenceCommand::class => $this->methodHandler,
                RequestCommand::class => $this->methodHandler,
                FreeCommand::class => $this->methodHandler,

]),
            new HandleClassNameInflector()
        );

        $this->priorityMiddleware = new PriorityMiddleware();

        $this->commandBus = new CommandBus([$this->priorityMiddleware, $handlerMiddleware]);
    }

    /**     
     * Tests if regular commands (no implementations of PriorityCommandInterface are executed).
     **/
    public function testRegularCommandIsExecuted()
    {
        $command = new AddTaskCommand();
        $this->commandBus->handle($command);

        $this->assertContains('handleAddTaskCommand', $this->methodHandler->getMethodsInvoked());
    }

    /**     
     * Tests if urgent commands are executed immediately.
     **/
    public function testUrgentCommand()
    {
        $command = new UrgentCommand();
        $this->commandBus->handle($command);

        $this->assertContains('handleUrgentCommand', $this->methodHandler->getMethodsInvoked());
    }

    /**     
     * Tests if sequence commands are executed before urgent commands if passed to the bus before the urgent comand.
     **/
    public function testSequenceBeforeUrgentCommand()
    {
        $urgent = new UrgentCommand();
        $sequence = new SequenceCommand();

        $this->commandBus->handle($sequence);
        $this->assertNotContains('handleSequenceCommand', $this->methodHandler->getMethodsInvoked());
        $this->commandBus->handle($urgent);
        $this->assertEquals(['handleSequenceCommand', 'handleUrgentCommand'], $this->methodHandler->getMethodsInvoked());
    }

    /**
     * Test to execute the request queue on a kernel.terminate event.
     */
    public function testExecuteRequestOnTermination()
    {
        $eventDispatcher = new EventDispatcher();

        $this->priorityMiddleware->executeQueueAtEvent(PriorityMiddleware::REQUEST, 'kernel.terminate', new SymfonyEventDispatcher($eventDispatcher));

        $this->commandBus->handle(new RequestCommand());
        $this->commandBus->handle(new SequenceCommand());
        $this->commandBus->handle(new SecondSequenceCommand());
        $this->assertNotContains('handleRequestCommand', $this->methodHandler->getMethodsInvoked());

        $eventDispatcher->dispatch('kernel.terminate');
        // all sequence commands come before request commands
        $this->assertEquals(['handleSequenceCommand', 'handleSecondSequenceCommand', 'handleRequestCommand'], $this->methodHandler->getMethodsInvoked());
    }

    /**
     * Test to execute a free command on execute all.
     */
    public function testFreeCommandAtExecuteAll()
    {
        $this->commandBus->handle(new FreeCommand());
        $this->assertNotContains('handleFreeCommand', $this->methodHandler->getMethodsInvoked());

        $this->priorityMiddleware->executeAll();
        $this->assertEquals(['handleFreeCommand'], $this->methodHandler->getMethodsInvoked());
    }

    /**
     * Test custom order in execute all.
     */
    public function testCustomOrderAtExecuteAll()
    {
        $this->commandBus->handle(new FreeCommand());
        $this->commandBus->handle(new RequestCommand());
        $this->assertNotContains('handleFreeCommand', $this->methodHandler->getMethodsInvoked());
        $this->assertNotContains('handleRequestCommand', $this->methodHandler->getMethodsInvoked());

        $this->priorityMiddleware->executeAll(['free']); // request should be done later in the defaults
        $this->assertEquals(['handleFreeCommand', 'handleRequestCommand'], $this->methodHandler->getMethodsInvoked());
    }

    /**
     * Test queue a command.
     */
    public function testFreeCommandWithMessaging()
    {
        $messagingSystem = new InMemoryMessaging();
        $this->priorityMiddleware->setMessagingSystem(PriorityMiddleware::FREE, $messagingSystem);
        $this->commandBus->handle(new FreeCommand());
        $this->assertNotContains('handleFreeCommand', $this->methodHandler->getMethodsInvoked());
        $messagingSystem->consume();
        $this->assertEquals(['handleFreeCommand'], $this->methodHandler->getMethodsInvoked());
    }

    /**
     * Test queue a command before setting the messaging system.
     */
    public function testMessagingWithCommandBeforeMessagingSystem()
    {
        $messagingSystem = new InMemoryMessaging();
        $this->commandBus->handle(new FreeCommand());
        $this->assertNotContains('handleFreeCommand', $this->methodHandler->getMethodsInvoked());
        $this->priorityMiddleware->setMessagingSystem(PriorityMiddleware::FREE, $messagingSystem);
        $messagingSystem->consume();
        $this->assertEquals(['handleFreeCommand'], $this->methodHandler->getMethodsInvoked());
    }
    
    /**
     * Test to execute everything left in the bus when it's destroyed
     */
    public function testExecuteAllOnDestruction()    
    {
        $this->commandBus->handle(new RequestCommand());
        $this->commandBus->handle(new SequenceCommand());
        $this->commandBus->handle(new SecondSequenceCommand());
        
        $this->priorityMiddleware->__destruct(); // Setting to null or unsetting doesn't call __destruct somehow; any improvements to this test are greatly appreciated
        // all sequence commands come before request commands
        $this->assertEquals(['handleSequenceCommand', 'handleSecondSequenceCommand', 'handleRequestCommand'], $this->methodHandler->getMethodsInvoked());
    }
}
