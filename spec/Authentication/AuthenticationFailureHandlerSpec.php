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

namespace spec\Sylius\Bundle\UserBundle\Authentication;

use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AuthenticationFailureHandlerSpec extends ObjectBehavior
{
    function let(
        HttpKernelInterface $httpKernel,
        HttpUtils $httpUtils,
        LoggerInterface $logger,
        TranslatorInterface $translator,
    ): void {
        $this->beConstructedWith($httpKernel, $httpUtils, [], $logger, $translator);
    }

    function it_extends_default_authentication_failure_handler(): void
    {
        $this->shouldHaveType(DefaultAuthenticationFailureHandler::class);
    }

    function it_is_a_authentication_failure_handler(): void
    {
        $this->shouldImplement(AuthenticationFailureHandlerInterface::class);
    }

    function it_returns_translated_json_response_if_request_is_xml_based(
        Request $request,
        AuthenticationException $authenticationException,
        TranslatorInterface $translator,
    ): void {
        $request->isXmlHttpRequest()->willReturn(true);
        $request->getLocale()->willReturn('fr');
        $authenticationException->getMessageKey()->willReturn('Invalid credentials.');
        $translator->trans('Invalid credentials.', [], 'security', 'fr')
            ->willReturn('Identifiants invalides.')
            ->shouldBeCalled()
        ;

        $this->onAuthenticationFailure($request, $authenticationException)->shouldHaveType(JsonResponse::class);
    }
}
