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

namespace Tests\Sylius\Bundle\UserBundle\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UserBundle\Provider\UsernameProvider;
use Sylius\Component\User\Canonicalizer\CanonicalizerInterface;
use Sylius\Component\User\Model\User;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UsernameProviderTest extends TestCase
{
    /** @var UserRepositoryInterface|MockObject */
    private MockObject $userRepositoryMock;

    /** @var CanonicalizerInterface|MockObject */
    private MockObject $canonicalizerMock;

    private UsernameProvider $usernameProvider;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->canonicalizerMock = $this->createMock(CanonicalizerInterface::class);
        $this->usernameProvider = new UsernameProvider(User::class, $this->userRepositoryMock, $this->canonicalizerMock);
    }

    public function testImplementsSymfonyUserProviderInterface(): void
    {
        $this->assertInstanceOf(UserProviderInterface::class, $this->usernameProvider);
    }

    public function testSupportsSyliusUserModel(): void
    {
        $this->assertTrue($this->usernameProvider->supportsClass(User::class));
    }

    public function testLoadsUserByUserName(): void
    {
        /** @var User&MockObject $userMock */
        $userMock = $this->createMock(User::class);
        $this->canonicalizerMock->expects($this->once())->method('canonicalize')->with('testUser')->willReturn('testuser');
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['usernameCanonical' => 'testuser'])->willReturn($userMock);
        $this->assertSame($userMock, $this->usernameProvider->loadUserByUsername('testUser'));
    }

    public function testUpdatesUserByUserName(): void
    {
        /** @var User&MockObject $userMock */
        $userMock = $this->createMock(User::class);
        $this->userRepositoryMock->expects($this->once())->method('find')->with(1)->willReturn($userMock);
        $userMock->expects($this->once())->method('getId')->willReturn(1);
        $this->assertSame($userMock, $this->usernameProvider->refreshUser($userMock));
    }

    public function testThrowExceptionWhenUnsupportedUserIsUsed(): void
    {
        /** @var UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->expectException(UnsupportedUserException::class);
        $this->usernameProvider->refreshUser($userMock);
    }
}
