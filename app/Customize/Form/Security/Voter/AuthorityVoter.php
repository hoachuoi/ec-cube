<?php

namespace Customize\Form\Security\Voter;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Member;
use Eccube\Repository\AuthorityRoleRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Eccube\Security\Voter\AuthorityVoter as BaseAuthorityVoter;

class AuthorityVoter extends BaseAuthorityVoter
{
    /**
     * @var AuthorityRoleRepository
     */
    protected $authorityRoleRepository;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    public function __construct(
        AuthorityRoleRepository $authorityRoleRepository,
        RequestStack $requestStack,
        EccubeConfig $eccubeConfig
    ) {
        $this->authorityRoleRepository = $authorityRoleRepository;
        $this->requestStack = $requestStack;
        $this->eccubeConfig = $eccubeConfig;
    }
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $path = null;

        try {
            $request = $this->requestStack->getMainRequest();
        } catch (\RuntimeException $e) {
            // requestが取得できない場合、棄権する(テストプログラムで不要なため)
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if (is_object($request)) {
            $path = rawurldecode($request->getPathInfo());
        }

        $warehouse = $token->getUser();
        
        if ($warehouse && method_exists($warehouse, 'getIsWarehouse') && $warehouse->getIsWarehouse()) {
            return $this->checkWarehouseAccess($path) ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
        }

        $Member = $token->getUser();
        if ($Member instanceof Member) {
            // 管理者のロールをチェック
            $AuthorityRoles = $this->authorityRoleRepository->findBy(['Authority' => $Member->getAuthority()]);
            $adminRoute = $this->eccubeConfig->get('eccube_admin_route');

            foreach ($AuthorityRoles as $AuthorityRole) {
                // 許可しないURLが含まれていればアクセス拒否
                try {
                    // 正規表現でURLチェック
                    $denyUrl = str_replace('/', '\/', $AuthorityRole->getDenyUrl());
                    if (preg_match("/^(\/{$adminRoute}{$denyUrl})/i", $path)) {
                        return VoterInterface::ACCESS_DENIED;
                    }
                } catch (\Exception $e) {
                    // 拒否URLの指定に誤りがある場合、エスケープさせてチェック
                    $denyUrl = preg_quote($AuthorityRole->getDenyUrl(), '/');
                    if (preg_match("/^(\/{$adminRoute}{$denyUrl})/i", $path)) {
                        return VoterInterface::ACCESS_DENIED;
                    }
                }
            }
        }

        return VoterInterface::ACCESS_GRANTED;
    }

    /**
     * Kiểm tra quyền truy cập của user warehouse
     */
    private function checkWarehouseAccess( $path )
    {
        if (strpos($path, '/manager/product') === 0) {
            return true;
        }
        return false;
    }
}
