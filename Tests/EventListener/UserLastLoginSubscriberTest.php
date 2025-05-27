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

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Bundle\UserBundle\EventListener\UserLastLoginSubscriber;
use Sylius\Bundle\UserBundle\UserEvents;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final class UserLastLoginSubscriberTest extends TestCase
{
    /** @var ObjectManager|MockObject */
    private MockObject $userManagerMock;

    private UserLastLoginSubscriber $userLastLoginSubscriber;

    protected function setUp(): void
    {
        $this->userManagerMock = $this->createMock(ObjectManager::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, UserInterface::class, null);
    }

    public function testSubscriber(): void
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, $this->userLastLoginSubscriber);
    }

    public function testItsSubscribedToEvents(): void
    {
        $this->assertSame([
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            UserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
        ], $this->userLastLoginSubscriber::getSubscribedEvents());
    }

    public function testUpdatesUserLastLoginOnSecurityInteractiveLogin(): void
    {
        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('setLastLogin')->with($this->isInstanceOf(\DateTimeInterface::class));
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->userLastLoginSubscriber->onSecurityInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testUpdatesUserLastLoginOnImplicitLogin(): void
    {
        /** @var UserEvent&MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('setLastLogin')->with($this->isInstanceOf(\DateTimeInterface::class));
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->userLastLoginSubscriber->onImplicitLogin($eventMock);
    }

    public function testUpdatesOnlySyliusUserSpecifiedInConstructor(): void
    {
        /** @var UserEvent&MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);

        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, 'FakeBundle\User\Model\User', null);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->never())->method('setLastLogin');
        $this->userManagerMock->expects($this->never())->method('persist');
        $this->userManagerMock->expects($this->never())->method('flush');

        $this->userLastLoginSubscriber->onImplicitLogin($eventMock);
    }

    public function testThrowsExceptionIfSubscriberIsUsedForClassOtherThanSyliusUserInterface(): void
    {
        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var \Symfony\Component\Security\Core\User\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, SymfonyUserInterface::class, null);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->userManagerMock->expects($this->never())->method('persist');
        $this->userManagerMock->expects($this->never())->method('flush');
        $this->expectException(\UnexpectedValueException::class);
        $this->userLastLoginSubscriber->onSecurityInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testSetsLastLoginWhenThereWasNoneAndIntervalIsPresentOnInteractiveLogin(): void
    {
        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, UserInterface::class, 'P1D');
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getLastLogin')->willReturn(null);
        $userMock->expects($this->once())->method('setLastLogin')->with($this->isInstanceOf(\DateTimeInterface::class));
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->userLastLoginSubscriber->onSecurityInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testSetsLastLoginWhenThereWasNoneAndIntervalIsPresentOnImplicitLogin(): void
    {
        /** @var UserEvent&MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, UserInterface::class, 'P1D');
        $userMock->expects($this->once())->method('getLastLogin')->willReturn(null);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('setLastLogin')->with($this->isInstanceOf(\DateTimeInterface::class));
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->userLastLoginSubscriber->onImplicitLogin($eventMock);
    }

    public function testDoesNothingWhenTrackingIntervalIsSetAndUserWasUpdatedWithinItOnInteractiveLogin(): void
    {
        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, UserInterface::class, 'P1D');
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $lastLogin = (new \DateTime())->modify('-6 hours');
        $userMock->expects($this->once())->method('getLastLogin')->willReturn($lastLogin);
        $userMock->expects($this->never())->method('setLastLogin');
        $this->userManagerMock->expects($this->never())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->never())->method('flush');
        $this->userLastLoginSubscriber->onSecurityInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testDoesNothingWhenTrackingIntervalIsSetAndUserWasUpdatedWithinItOnImplicitLogin(): void
    {
        /** @var UserEvent&MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, UserInterface::class, 'P1D');
        $lastLogin = (new \DateTime())->modify('-6 hours');
        $userMock->expects($this->once())->method('getLastLogin')->willReturn($lastLogin);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->never())->method('setLastLogin');
        $this->userManagerMock->expects($this->never())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->never())->method('flush');
        $this->userLastLoginSubscriber->onImplicitLogin($eventMock);
    }

    public function testUpdatesLastLoginWhenThePreviousIsOlderThanTheIntervalOnInteractiveLogin(): void
    {
        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, UserInterface::class, 'P1D');
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $lastLogin = (new \DateTime())->modify('-3 days');
        $userMock->expects($this->once())->method('getLastLogin')->willReturn($lastLogin);
        $userMock->expects($this->once())->method('setLastLogin')->with($this->isInstanceOf(\DateTimeInterface::class));
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->userLastLoginSubscriber->onSecurityInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testUpdatesLastLoginWhenThePreviousIsOlderThanTheIntervalOnImplicitLogin(): void
    {
        /** @var UserEvent&MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var \Sylius\Component\User\Model\UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->userLastLoginSubscriber = new UserLastLoginSubscriber($this->userManagerMock, UserInterface::class, 'P1D');
        $lastLogin = (new \DateTime())->modify('-3 days');
        $userMock->expects($this->once())->method('getLastLogin')->willReturn($lastLogin);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('setLastLogin')->with($this->isInstanceOf(\DateTimeInterface::class));
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->userLastLoginSubscriber->onImplicitLogin($eventMock);
    }
}
