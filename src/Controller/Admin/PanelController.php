<?php

namespace KJanczyk\PimcoreSOMLBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/soml', name: 'soml_admin_')]
class PanelController extends AbstractController
{
    #[Route('/panel', name: 'panel', methods: ['GET'])]
    public function panel(): Response
    {
        return new Response(
            '<div style="padding:20px;font:14px/1.4 system-ui,sans-serif">
                <h1 style="margin-top:0;">SOML Bundle Panel</h1>
            </div>'
        );
    }
}
