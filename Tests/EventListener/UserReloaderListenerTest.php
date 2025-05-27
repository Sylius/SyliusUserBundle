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

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UserBundle\EventListener\UserReloaderListener;
use Sylius\Bundle\UserBundle\Reloader\UserReloaderInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class UserReloaderListenerTest extends TestCase
{
    /** @var UserReloaderInterface|MockObject */
    private MockObject $userReloaderMock;

    private UserReloaderListener $userReloaderListener;

    protected function setUp(): void
    {
        $this->userReloaderMock = $this->createMock(UserReloaderInterface::class);
        $this->userReloaderListener = new UserReloaderListener($this->userReloaderMock);
    }

    public function testReloadsUser(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($userMock);
        $this->userReloaderMock->expects($this->once())->method('reloadUser')->with($userMock);
        $this->userReloaderListener->reloadUser($eventMock);
    }

    public function testThrowsExceptionWhenReloadingNotAUserInterface(): void
    {
        /** @var GenericEvent&MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn('user');
        $this->userReloaderMock->expects($this->never())->method('reloadUser');
        $this->expectException(InvalidArgumentException::class);
        $this->userReloaderListener->reloadUser($eventMock);
    }
}
