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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UserBundle\EventListener\UserDeleteListener;
use Sylius\Component\User\Model\UserInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class UserDeleteListenerTest extends TestCase
{
    /** @var TokenStorageInterface|MockObject */
    private MockObject $tokenStorageMock;

    /** @var RequestStack|MockObject */
    private MockObject $requestStackMock;

    /** @var SessionInterface|MockObject */
    private MockObject $sessionMock;

    /** @var FlashBagInterface|MockObject */
    private MockObject $flashBagMock;

    private UserDeleteListener $userDeleteListener;

    protected function setUp(): void
    {
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $this->flashBagMock = $this->createMock(FlashBagInterface::class);
        $this->userDeleteListener = new UserDeleteListener($this->tokenStorageMock, $this->requestStackMock);
    }

    public function testDeletesUserIfItIsDifferentThanCurrentlyLoggedOne(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var UserInterface&MockObject $userToBeDeletedMock */
        $userToBeDeletedMock = $this->createMock(UserInterface::class);
        /** @var UserInterface&MockObject $currentlyLoggedUserMock */
        $currentlyLoggedUserMock = $this->createMock(UserInterface::class);
        /** @var TokenInterface&MockObject $tokenInterfaceMock */
        $tokenInterfaceMock = $this->createMock(TokenInterface::class);

        $eventMock->expects($this->once())->method('getSubject')->willReturn($userToBeDeletedMock);
        $userToBeDeletedMock->expects($this->once())->method('getId')->willReturn(11);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenInterfaceMock);
        $currentlyLoggedUserMock->expects($this->once())->method('getId')->willReturn(1);
        $tokenInterfaceMock->expects($this->once())->method('getUser')->willReturn($currentlyLoggedUserMock);
        $eventMock->expects($this->never())->method('stopPropagation');
        $this->flashBagMock->expects($this->never())->method('add');

        $this->userDeleteListener->deleteUser($eventMock);
    }

    public function testDeletesUserIfNoUserIsLoggedIn(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var UserInterface&MockObject $userToBeDeletedMock */
        $userToBeDeletedMock = $this->createMock(UserInterface::class);
        /** @var TokenInterface&MockObject $tokenInterfaceMock */
        $tokenInterfaceMock = $this->createMock(TokenInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($userToBeDeletedMock);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenInterfaceMock);
        $tokenInterfaceMock->expects($this->once())->method('getUser')->willReturn(null);
        $eventMock->expects($this->never())->method('stopPropagation');
        $eventMock->expects($this->never())->method('setErrorCode');
        $eventMock->expects($this->never())->method('setMessage');
        $this->flashBagMock->expects($this->never())->method('add');

        $this->userDeleteListener->deleteUser($eventMock);
    }

    public function testDeletesUserIfThereIsNoToken(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var UserInterface&MockObject $userToBeDeletedMock */
        $userToBeDeletedMock = $this->createMock(UserInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($userToBeDeletedMock);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn(null);
        $eventMock->expects($this->never())->method('stopPropagation');
        $eventMock->expects($this->never())->method('setErrorCode');
        $eventMock->expects($this->never())->method('setMessage');
        $this->flashBagMock->expects($this->never())->method('add');
        $this->userDeleteListener->deleteUser($eventMock);
    }

    public function testDoesNotAllowToDeleteCurrentlyLoggedUser(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var UserInterface&MockObject $userToBeDeletedMock */
        $userToBeDeletedMock = $this->createMock(UserInterface::class);
        /** @var UserInterface&MockObject $currentlyLoggedInUserMock */
        $currentlyLoggedInUserMock = $this->createMock(UserInterface::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);

        $this->requestStackMock->expects($this->once())->method('getSession')->willReturn($this->sessionMock);
        $this->sessionMock->expects($this->once())->method('getBag')->with('flashes')->willReturn($this->flashBagMock);

        $eventMock->expects($this->once())->method('getSubject')->willReturn($userToBeDeletedMock);
        $userToBeDeletedMock->expects($this->once())->method('getId')->willReturn(1);
        $userToBeDeletedMock->expects($this->once())->method('getRoles')->willReturn(['ROLE_ADMINISTRATION_ACCESS']);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenMock);
        $currentlyLoggedInUserMock->expects($this->once())->method('getId')->willReturn(1);
        $currentlyLoggedInUserMock->expects($this->once())->method('getRoles')->willReturn(['ROLE_ADMINISTRATION_ACCESS']);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($currentlyLoggedInUserMock);
        $eventMock->expects($this->once())->method('stopPropagation');
        $eventMock->expects($this->once())->method('setErrorCode')->with(Response::HTTP_UNPROCESSABLE_ENTITY);
        $eventMock->expects($this->once())->method('setMessage')->with('Cannot remove currently logged in user.');
        $this->flashBagMock->expects($this->once())->method('add')->with('error', 'Cannot remove currently logged in user.');
        $this->userDeleteListener->deleteUser($eventMock);
    }

    public function testDeletesShopUserEvenIfAdminUserHasSameId(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var UserInterface&MockObject $userToBeDeletedMock */
        $userToBeDeletedMock = $this->createMock(UserInterface::class);
        /** @var UserInterface&MockObject $currentlyLoggedInUserMock */
        $currentlyLoggedInUserMock = $this->createMock(UserInterface::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);

        $eventMock->expects($this->once())->method('getSubject')->willReturn($userToBeDeletedMock);
        $userToBeDeletedMock->expects($this->once())->method('getId')->willReturn(1);
        $userToBeDeletedMock->expects($this->once())->method('getRoles')->willReturn(['ROLE_API_ACCESS']);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenMock);
        $currentlyLoggedInUserMock->expects($this->once())->method('getId')->willReturn(1);
        $currentlyLoggedInUserMock->expects($this->once())->method('getRoles')->willReturn(['ROLE_ADMINISTRATION_ACCESS']);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($currentlyLoggedInUserMock);
        $eventMock->expects($this->never())->method('stopPropagation');
        $eventMock->expects($this->never())->method('setErrorCode');
        $eventMock->expects($this->never())->method('setMessage');
        $this->flashBagMock->expects($this->never())->method('add');

        $this->userDeleteListener->deleteUser($eventMock);
    }
}
