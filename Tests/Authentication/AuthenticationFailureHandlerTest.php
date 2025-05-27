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
use Sylius\Bundle\UserBundle\Authentication\AuthenticationFailureHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

final class AuthenticationFailureHandlerTest extends TestCase
{
    /** @var HttpKernelInterface|MockObject */
    private MockObject $httpKernelMock;

    /** @var HttpUtils|MockObject */
    private MockObject $httpUtilsMock;

    private AuthenticationFailureHandler $authenticationFailureHandler;

    protected function setUp(): void
    {
        $this->httpKernelMock = $this->createMock(HttpKernelInterface::class);
        $this->httpUtilsMock = $this->createMock(HttpUtils::class);
        $this->authenticationFailureHandler = new AuthenticationFailureHandler($this->httpKernelMock, $this->httpUtilsMock);
    }

    public function testAAuthenticationFailureHandler(): void
    {
        $this->assertInstanceOf(AuthenticationFailureHandlerInterface::class, $this->authenticationFailureHandler);
    }

    public function testReturnsJsonResponseIfRequestIsXmlBased(): void
    {
        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var AuthenticationException&MockObject $authenticationExceptionMock */
        $authenticationExceptionMock = $this->createMock(AuthenticationException::class);
        $requestMock->expects($this->once())->method('isXmlHttpRequest')->willReturn(true);
        $authenticationExceptionMock->expects($this->once())->method('getMessageKey')->willReturn('Invalid credentials.');
        $this->assertInstanceOf(JsonResponse::class, $this->authenticationFailureHandler->onAuthenticationFailure($requestMock, $authenticationExceptionMock));
    }
}
