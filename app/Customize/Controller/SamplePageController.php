<?php

namespace Customize\Controller;

use Composer\DependencyResolver\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Repository\BaseInfoRepository;
use Psr\Log\LoggerInterface;

class SamplePageController extends \Eccube\Controller\AbstractController
{
    protected $BaseInfo;

    /**
     * SamplePageController constructor.
     * @param BaseInfoRepository $BaseInfoRepository
     */
    public function __construct(BaseInfoRepository $BaseInfoRepository)
    {
        $this->BaseInfo = $BaseInfoRepository->get();
    }
    /**
     * @Method("GET")
     * @Route("/entryyyy")
     */
    public function bac(LoggerInterface $logger){
        $logger->info('--- imageProcess START ---');
    }
    // public function testMethod()
    // {
    //     return new Response('Hello, world! Xuan Bac');
    //     // return ['name' => ' , Lại là Bắc Đây, ID= '];
    //     // return $this->redirectToRoute('help_about');// chjuyển hướng đến route help_about
        
    //     // sử dụng entity manager
    //     // $product = $this->entityManager->getRepository('Eccube\Entity\Product')->find(2);
    //     // return new Response($product->getName());

    //     // sử dụng service không có sẵn
    //     // return new Response(' Shop name is '. $this->BaseInfo->getShopName());

    //     //controller không hiển thị ra màn hình
    //     // return  new Response(
    //     //     '',
    //     //     Response::HTTP_OK,
    //     //     array('Content-Type' => 'text/plain; charset=utf-8')
    //     // ); //test py postman
        
    // }   
}
