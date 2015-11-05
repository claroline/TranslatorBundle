<?php

namespace Claroline\TranslatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;

class TranslatorController extends Controller
{
    /**
     * @EXT\Route("/app", name="claroline_translator_app_index")
     * @EXT\Template
     *
     * @return Response
     */
    public function appAction()
    {
        $repositories = $this->container->get('claroline.translation.manager.git_manager')->getRepositories();

        return array('repositories' => $repositories);
    }

    /**         
     * @EXT\Route("/{vendor}/{bundle}/{lang}/latest.json", name="claroline_translator_get_latest")
     *
     * @return Response
     */
    public function getLastTranslationsAction($vendor, $bundle, $lang) {
        $translationManager = $this->container->get('claroline.translation.manager.translation_manager');
        $translations = $translationManager->getLastTranslations($vendor, $bundle, $lang);
        $data = $this->container->get('serializer')->serialize($translations, 'json');

        $response = new JsonResponse();
        $response->setContent($data);

        return $response;
    }
}
