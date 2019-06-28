<?php
namespace Vanio\ApiBundle\Security;

use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    /** @var DefaultAuthenticationFailureHandler */
    private $authenticationFailureHandler;

    /** @var SerializerInterface */
    private $serializer;

    /** @var TranslatorInterface|null */
    private $translator;

    public function __construct(
        DefaultAuthenticationFailureHandler $authenticationFailureHandler,
        SerializerInterface $serializer,
        TranslatorInterface $translator
    ) {
        $this->authenticationFailureHandler = $authenticationFailureHandler;
        $this->serializer = $serializer;
        $this->translator = $translator;
    }

    public function getOptions(): array
    {
        return $this->authenticationFailureHandler->getOptions();
    }

    public function setOptions(array $options): void
    {
        $this->authenticationFailureHandler->setOptions($options);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $response = $this->authenticationFailureHandler->onAuthenticationFailure($request, $exception);

        if (!$response->isRedirection() || $request->getRequestFormat() === 'html') {
            return $response;
        }

        $format = $request->getRequestFormat();
        $error = $this->translator->trans($exception->getMessageKey(), $exception->getMessageData(), 'security');

        if ($format === 'xml') {
            return sprintf(
                '<?xml version="1.0" encoding="UTF-8"?>
                    <response>
                      <code>%d</code>
                      <message><![CDATA[%s]]></message>
                      <errors>
                        <error><![CDATA[%s]]></error>
                      </errors>
                    </response>
                ',
                Response::HTTP_UNAUTHORIZED,
                'Unauthorized',
                $error
            );
        }

        try {
            $data = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized',
                'errors' => [$error],
            ];
            $content = $this->serializer->serialize($data, $format);

            return new Response($content, Response::HTTP_UNAUTHORIZED);
        } catch (UnsupportedFormatException $e) {}

        return $response;
    }
}
