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

namespace Tests\Sylius\Bundle\UserBundle\Authentication;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UserBundle\Authentication\AuthenticationSuccessHandler;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

final class AuthenticationSuccessHandlerTest extends TestCase
{
    /** @var HttpUtils|MockObject */
    private MockObject $httpUtilsMock;

    private AuthenticationSuccessHandler $authenticationSuccessHandler;

    protected function setUp(): void
    {
        $this->httpUtilsMock = $this->createMock(HttpUtils::class);
        $this->authenticationSuccessHandler = new AuthenticationSuccessHandler($this->httpUtilsMock);
    }

    public function testAAuthenticationSuccessHandler(): void
    {
        $this->assertInstanceOf(AuthenticationSuccessHandlerInterface::class, $this->authenticationSuccessHandler);
    }

    public function testReturnsJsonResponseIfRequestIsXmlBased(): void
    {
        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface&MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var UserInterface&MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $requestMock->expects($this->once())->method('isXmlHttpRequest')->willReturn(true);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->authenticationSuccessHandler->onAuthenticationSuccess($requestMock, $tokenMock);
    }
}
