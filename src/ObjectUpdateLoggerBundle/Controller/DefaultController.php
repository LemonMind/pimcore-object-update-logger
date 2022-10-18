<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends FrontendController
{
    /**
     * @Route("/lemonmind_object_update_logger")
     */
    public function indexAction(Request $request)
    {
        return new Response('Hello world from lemonmind_object_update_logger');
    }
}
