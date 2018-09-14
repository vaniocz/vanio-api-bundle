<?php
namespace Vanio\ApiBundle\Security;

use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    /** @var DefaultAuthenticationSuccessHandler */
    private $authenticationSuccessHandler;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(
        DefaultAuthenticationSuccessHandler $authenticationSuccessHandler,
        SerializerInterface $serializer
    ) {
        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
        $this->serializer = $serializer;
    }

    public function getOptions(): array
    {
        return $this->authenticationSuccessHandler->getOptions();
    }

    public function setOptions(array $options): void
    {
        $this->authenticationSuccessHandler->setOptions($options);
    }

    public function getProviderKey(): string
    {
        return $this->authenticationSuccessHandler->getProviderKey();
    }

    /**
     * @param string $providerKey
     */
    public function setProviderKey($providerKey): void
    {
        $this->authenticationSuccessHandler->setProviderKey($providerKey);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        if ($request->getRequestFormat() !== 'html') {
            try {
                $data = $token->getUser() instanceof \JsonSerializable
                    ? $token->getUser()->jsonSerialize()
                    : ['username' => $token->getUsername()];

                return new Response($this->serializer->serialize($data, $request->getRequestFormat()));
            } catch (UnsupportedFormatException $e) {}
        }

        return $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }
}
