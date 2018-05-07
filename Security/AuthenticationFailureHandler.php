<?php
namespace Vanio\ApiBundle\Security;

use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var TranslatorInterface|null */
    private $translator;

    /**
     * @param HttpKernelInterface $httpKernel
     * @param HttpUtils $httpUtils
     * @param mixed[] $options
     * @param LoggerInterface|null $logger
     * @param SerializerInterface $serializer
     * @param TranslatorInterface $translator
     */
    public function __construct(
        HttpKernelInterface $httpKernel,
        HttpUtils $httpUtils,
        array $options,
        ?LoggerInterface $logger,
        SerializerInterface $serializer,
        TranslatorInterface $translator
    ) {
        parent::__construct($httpKernel, $httpUtils, $options, $logger);
        $this->serializer = $serializer;
        $this->translator = $translator;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->getRequestFormat() !== 'html') {
            try {
                $error = $this->translator->trans(
                    $exception->getMessageKey(),
                    $exception->getMessageData(),
                    'security'
                );
                $data = [
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'errors' => [$error],
                ];
                $content = $this->serializer->serialize($data, $request->getRequestFormat());

                return new Response($content, 401);
            } catch (UnsupportedFormatException $e) {}
        }

        return parent::onAuthenticationFailure($request, $exception);
    }
}
