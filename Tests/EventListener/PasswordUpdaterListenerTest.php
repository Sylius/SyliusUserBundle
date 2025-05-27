<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\Bundle\UserBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UserBundle\EventListener\PasswordUpdaterListener;
use Sylius\Component\User\Model\UserInterface;
use Sylius\Component\User\Security\PasswordUpdaterInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use TypeError;

final class PasswordUpdaterListenerTest extends TestCase
{
    /** @var PasswordUpdaterInterface|MockObject */
    private MockObject $passwordUpdaterMock;

    private PasswordUpdaterListener $passwordUpdaterListener;

    protected function setUp(): void
    {
        $this->passwordUpdaterMock = $this->createMock(PasswordUpdaterInterface::class);
        $this->passwordUpdaterListener = new PasswordUpdaterListener($this->passwordUpdaterMock);
    }

    public function testUpdatesPasswordForGenericEvent(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($userMock);
        $userMock->expects($this->once())->method('getPlainPassword')->willReturn('testPassword');
        $this->passwordUpdaterMock->expects($this->once())->method('updatePassword')->with($userMock);
        $this->passwordUpdaterListener->genericEventUpdater($eventMock);
    }

    public function testAllowsToUpdatePasswordForGenericEventForUserInterfaceImplementationOnly(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn('user');
        $this->expectException(TypeError::class);
        $this->passwordUpdaterListener->genericEventUpdater($eventMock);
    }

    public function testUpdatesPasswordOnPrePersistDoctrineEvent(): void
    {
        /** @var LifecycleEventArgs&MockObject $eventMock */
        $eventMock = $this->createMock(LifecycleEventArgs::class);
        /** @var UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $eventMock->expects($this->once())->method('getObject')->willReturn($userMock);
        $userMock->expects($this->once())->method('getPlainPassword')->willReturn('testPassword');
        $this->passwordUpdaterMock->expects($this->once())->method('updatePassword')->with($userMock);
        $this->passwordUpdaterListener->prePersist($eventMock);
    }

    public function testUpdatesPasswordOnPreUpdateDoctrineEvent(): void
    {
        /** @var LifecycleEventArgs&MockObject $eventMock */
        $eventMock = $this->createMock(LifecycleEventArgs::class);
        /** @var UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $eventMock->expects($this->once())->method('getObject')->willReturn($userMock);
        $userMock->expects($this->once())->method('getPlainPassword')->willReturn('testPassword');
        $this->passwordUpdaterMock->expects($this->once())->method('updatePassword')->with($userMock);
        $this->passwordUpdaterListener->preUpdate($eventMock);
    }

    public function testUpdatesPasswordOnPrePersistDoctrineEventForUserInterfaceImplementationOnly(): void
    {
        /** @var LifecycleEventArgs&MockObject $eventMock */
        $eventMock = $this->createMock(LifecycleEventArgs::class);
        $eventMock->expects($this->once())->method('getObject')->willReturn('user');
        $this->passwordUpdaterMock->expects($this->never())->method('updatePassword');
        $this->passwordUpdaterListener->prePersist($eventMock);
    }

    public function testUpdatesPasswordOnPreUpdateDoctrineEventForUserInterfaceImplementationOnly(): void
    {
        /** @var LifecycleEventArgs&MockObject $eventMock */
        $eventMock = $this->createMock(LifecycleEventArgs::class);
        $eventMock->expects($this->once())->method('getObject')->willReturn('user');
        $this->passwordUpdaterMock->expects($this->never())->method('updatePassword');
        $this->passwordUpdaterListener->preUpdate($eventMock);
    }
}
