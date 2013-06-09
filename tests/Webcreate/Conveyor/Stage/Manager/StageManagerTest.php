<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Webcreate\Conveyor\Stage\Manager\StageManager;

class StageManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;
    protected $context;

    /**
     * @var StageManager
     */
    protected $stageManager;

    public function setUp()
    {
        $this->dispatcher = $this->getDispatcherMock();
        $this->context = $this->getMockBuilder('Webcreate\Conveyor\Context')->getMock();
        $this->stageManager = new StageManager($this->context, $this->dispatcher);

    }

    protected function getDispatcherMock()
    {
        if (null === $this->dispatcher) {
            $this->dispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        }

        return $this->dispatcher;
    }

    protected function createStageMock($testExecute = true)
    {
        $stage = $this->getMockForAbstractClass('Webcreate\Conveyor\Stage\AbstractStage');
        if ($testExecute) {
            $stage
                ->expects($this->once())
                ->method('execute')
            ;
        }
        $stage
            ->expects($this->any())
            ->method('supports')
            ->will($this->returnValue(true))
        ;

        return $stage;
    }

    public function testStageManagerStartsWithoutStages()
    {
        $result = $this->stageManager->getStages();

        $this->assertEmpty($result);
    }

    public function testAddStage()
    {
        $stage = $this->getMockForAbstractClass('Webcreate\Conveyor\Stage\AbstractStage');

        $this->stageManager->addStage('test.stage', $stage);

        $result = $this->stageManager->getStages();

        $this->assertEquals(
            array(
                'test.stage' => array('name' => 'test.stage', 'stage' => $stage)
            ),
            $result
        );
    }

    public function testExecuteExecutesAllStages()
    {
        $this->stageManager->addStage('stage1', $this->createStageMock());
        $this->stageManager->addStage('stage2', $this->createStageMock());
        $this->stageManager->execute();
    }

    public function testExecuteExecutesOnlySelectedStages()
    {
        $this->stageManager->addStage('stage1', $this->createStageMock());
        $this->stageManager->addStage('stage2', $this->createStageMock());

        $notExecutedStage = $this->createStageMock(false);
        $notExecutedStage->expects($this->never())->method('execute');
        $notExecutedStage->expects($this->never())->method('supports');
        $this->stageManager->addStage('stage3', $notExecutedStage);

        $this->stageManager->addStage('stage4', $this->createStageMock());

        $this->stageManager->execute(array('stage1', 'stage2', 'stage4'));
    }

    public function testExecuteHaltsWhenStageReturnsFalse()
    {
        $stage1 = $this->createStageMock(false);
        $stage1
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(false))
        ;

        $stage2 = $this->createStageMock(false);
        $stage2->expects($this->never())->method('supports');
        $stage2->expects($this->never())->method('execute');

        $this->stageManager->addStage('stage1', $stage1);
        $this->stageManager->addStage('stage2', $stage2);

        $this->stageManager->execute();
    }

    public function testExecuteDispatchesPreExecuteEvent()
    {
        $this->dispatcher
            ->expects($this->at(0))
            ->method('dispatch')
            ->with(\Webcreate\Conveyor\Event\StageEvents::STAGE_PRE_EXECUTE, $this->anything())
        ;

        $this->stageManager->addStage('stage1', $this->createStageMock());
        $this->stageManager->execute();
    }

    public function testExecuteDispatchesPostExecuteEvent()
    {
        $this->dispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with(\Webcreate\Conveyor\Event\StageEvents::STAGE_POST_EXECUTE, $this->anything())
        ;

        $this->stageManager->addStage('stage1', $this->createStageMock());
        $this->stageManager->execute();
    }
}
