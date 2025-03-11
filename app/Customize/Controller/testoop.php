<?php

namespace Customize\Controller;

use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


class testoop extends \Eccube\Controller\AbstractController
{
    public const salary = 100000;
 
    public function __call($method, $args)
    {
        if ($method == 'getSalary') {
            switch(count($args)) {
                case 0:
                    return self::salary;
                case 1:
                    return self::salary * $args[0];
                case 2:
                    return self::salary * $args[0] * $args[1];
            }
        }
    }
    /**
     * @Route("/aaaa")
     * @Method("GET")
     */
    public function testMethod()
    {
        return new Response('Hello, world! Xuan Bac'.$this->getSalary(6,2)." khong cos doi so".$this->getSalary());
    }
}

