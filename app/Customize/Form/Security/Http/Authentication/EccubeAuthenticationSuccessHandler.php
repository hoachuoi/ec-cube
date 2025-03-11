<?php

namespace Customize\Form\Security\Http\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Eccube\Security\Http\Authentication\EccubeAuthenticationSuccessHandler as BaseEccubeAuthenticationSuccessHandler;
class  EccubeAuthenticationSuccessHandler extends BaseEccubeAuthenticationSuccessHandler
{
    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ? Response
    {
        try {
            $response = parent::onAuthenticationSuccess($request, $token);
        } catch (RouteNotFoundException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e, $e->getCode());
        }

        $user = $token->getUser();

        if (method_exists($user, 'getIsWarehouse') && $user->getIsWarehouse()) {
            $response->setTargetUrl('/manager/product');
            return $response;
        }

        if (preg_match('/^https?:\\\\/i', $response->getTargetUrl())) {
            $response->setTargetUrl($request->getUriForPath('/'));
            return $response;
        }

    }    
}