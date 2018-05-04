<?php
namespace Vanio\ApiBundle\Security;

use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param HttpUtils $httpUtils
     * @param mixed[] $options
     * @param SerializerInterface $serializer
     */
    public function __construct(HttpUtils $httpUtils, array $options, SerializerInterface $serializer)
    {
        parent::__construct($httpUtils, $options);
        $this->serializer = $serializer;
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

        return parent::onAuthenticationSuccess($request, $token);
    }
}
