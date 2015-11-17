<?php

namespace Claroline\TranslatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializationContext;


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
        $locales = $this->container
        ->get('claroline.translation.manager.translation_manager')->getAvailableLocales();

        return array('repositories' => $repositories, 'locales' => $locales);
    }

    /**         
     * @EXT\Route(
     *     "/{vendor}/{bundle}/{lang}/latest.json", 
     *     name="claroline_translator_get_latest",
     *     defaults={"page"=1},
     *     options={"expose"=true}
     * )
     * @return Response
     */
    public function getLastTranslationsAction($vendor, $bundle, $lang) 
    {
        $translationManager = $this->container->get('claroline.translation.manager.translation_manager');
        $translations = $translationManager->getLastTranslations($vendor, $bundle, $lang);
        $context = new SerializationContext();
        $context->setGroups('translator');
        $data = $this->container->get('serializer')->serialize($translations, 'json', $context);
        $response = new JsonResponse();
        $response->setContent($data);

        return $response;
    }


    /**
     * @EXT\Route(
     *     "/{vendor}/{bundle}/{lang}/{page}/search/{search}/latest.json", 
     *     name="claroline_translator_search_latest",
     *     defaults={"page"=1},
     *     options={"expose"=true}
     * )
     */
    public function searchLastTranslationsAction($vendor, $bundle, $lang, $search) 
    {
        $translationManager = $this->container->get('claroline.translation.manager.translation_manager');
        $translations = $translationManager->searchLastTranslations($vendor, $bundle, $lang, $search);
        $context = new SerializationContext();
        $context->setGroups('translator');
        $data = $this->container->get('serializer')->serialize($translations, 'json', $context);
        $response = new JsonResponse();
        $response->setContent($data);

        return $response;
    }


    /**              
     * @EXT\Route(
     *     "/translation/add", 
     *     name="claroline_translator_add_translation",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function addTranslationAction()
    {
        $translationManager = $this->container->get('claroline.translation.manager.translation_manager');

        $value  = $this->get('request')->request->get('translation');
        $vendor = $this->get('request')->request->get('vendor');
        $bundle = $this->get('request')->request->get('bundle');
        $domain = $this->get('request')->request->get('domain');
        $lang   = $this->get('request')->request->get('lang');
        $key    = $this->get('request')->request->get('key');

        $translation = $translationManager->addTranslation(
            $vendor,
            $bundle,
            $domain,
            $lang,
            $key,
            $value
        );

        $context = new SerializationContext();
        $context->setGroups('translator');
        $data = $this->container->get('serializer')->serialize($translation, 'json', $context);

        $response = new JsonResponse();
        $response->setContent($data);

        return $response;
    }

    /**              
     * @EXT\Route(
     *     "/{vendor}/{bundle}/{lang}/{key}/translation.json", 
     *     name="claroline_translator_get_translation_info",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function loadTranslationsInfosAction($vendor, $bundle, $lang, $key)
    {
        $translationManager = $this->container
            ->get('claroline.translation.manager.translation_manager');
        $translations = $translationManager
            ->getTranslationInfo($vendor, $bundle, $lang, $key);
        $data = $this->container
            ->get('serializer')->serialize($translations, 'json');

        $response = new JsonResponse();
        $response->setContent($data);

        return $response;
    }

    /**         
     * @EXT\Route(
     *     "/langs.json", 
     *     name="claroline_translator_langs",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function getLangAction()
    {
        $locales = $this->container
            ->get('claroline.translation.manager.translation_manager')
            ->getAvailableLocales();

        return new JsonResponse($locales);
    }

    /**         
     * @EXT\Route(
     *     "/repositories.json", 
     *     name="claroline_translator_repositories",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function getRepositories()
    {
        $repositories = $this->container
            ->get('claroline.translation.manager.git_manager')
            ->getRepositories();

        return new JsonResponse($repositories);
    }

    /**              
     * @EXT\Route(
     *     "/{vendor}/{bundle}/{lang}/{key}/user/lock", 
     *     name="claroline_translator_user_lock",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function clickUserLockAction($vendor, $bundle, $lang, $key)
    {
        $this->container
            ->get('claroline.translation.manager.translation_manager')
            ->lockUserAction($vendor, $bundle, $lang, $key);

        return new JsonResponse();
    }

    /**              
     * @EXT\Route(
     *     "/{vendor}/{bundle}/{lang}/{key}/admin/lock", 
     *     name="claroline_translator_admin_lock",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function clickAdminLockAction($vendor, $bundle, $lang, $key)
    {
        $this->container
            ->get('claroline.translation.manager.translation_manager')
            ->lockAdminAction($vendor, $bundle, $lang, $key);

        return new JsonResponse();
    }
}
