<?php
namespace Vanio\ApiBundle\Security;

use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(HttpUtils $httpUtils, string $targetUrl = '/', SerializerInterface $serializer)
    {
        parent::__construct($httpUtils, $targetUrl);
        $this->serializer = $serializer;
    }

    public function onLogoutSuccess(Request $request): Response
    {
        $format = $request->getRequestFormat();

        if ($format !== 'html') {
            $content = $this->serializer->serialize(['success' => true], $format);

            return new Response($content);
        }

        return parent::onLogoutSuccess($request);
    }
}
