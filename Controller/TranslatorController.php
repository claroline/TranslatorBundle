<?php

namespace Claroline\TranslatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializationContext;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation as SEC;
use Claroline\TranslatorBundle\Form\TranslatorType;
use Claroline\ForumBundle\Entity\Subject;
use Claroline\ForumBundle\Entity\Message;
use Claroline\TranslatorBundle\Entity\TranslationItem;

class TranslatorController extends Controller
{
    /**
     * @DI\InjectParams({
     *     "authorization" = @DI\Inject("security.authorization_checker"),
     *     "tokenStorage"  = @DI\Inject("security.token_storage"),
     * })
     */
    public function __construct($authorization, $tokenStorage)
    {
        $this->authorization = $authorization;
        $this->tokenStorage  = $tokenStorage;
    }

    /**
     * @EXT\Route("/app", name="claroline_translator_app_index")
     * @EXT\Template
     *
     * @return Response
     */
    public function appAction()
    {
        $this->checkIsTranslator();

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
        $this->checkIsTranslator();

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
        $this->checkIsTranslator();

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
        $this->checkIsTranslator();

        $translationManager = $this->container->get('claroline.translation.manager.translation_manager');
        $value  = $this->get('request')->request->get('translation');
        $translationItem = $this->get('request')->request->get('translation_item');
        $translationItem = $translationManager->find($translationItem);

        if ($translationItem->isAdminLocked()) {
            return new JsonResponse();
        }

        $translations = $translationItem->getTranslations();

        $translation = ($translations[0]->getTranslation() !== $value) ?
            $translationManager->addTranslation($translationItem, $value):
            $translations[0];

        $context = new SerializationContext();
        $context->setGroups('translator');
        $data = $this->container->get('serializer')->serialize($translation, 'json', $context);

        $response = new JsonResponse();
        $response->setContent($data);

        return $response;
    }

    /**              
     * @EXT\Route(
     *     "/{vendor}/{bundle}/{domain}/{lang}/{key}/translation.json", 
     *     name="claroline_translator_get_translation_info",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function loadTranslationsInfosAction($vendor, $bundle, $domain, $lang, $key)
    {
        $this->checkIsTranslator();

        $translationManager = $this->container
            ->get('claroline.translation.manager.translation_manager');
        $context = new SerializationContext();
        $context->setGroups('infos');
        $translations = $translationManager
            ->getTranslationItem($vendor, $bundle, $domain, $lang, $key);
        $data = $this->container
            ->get('serializer')->serialize($translations, 'json', $context);

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
        $this->checkIsTranslator();

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
        $this->checkIsTranslator();

        $repositories = $this->container
            ->get('claroline.translation.manager.git_manager')
            ->getRepositories();

        return new JsonResponse($repositories);
    }

    /**              
     * @EXT\Route(
     *     "/{translationItem}/user/lock", 
     *     name="claroline_translator_user_lock",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function clickUserLockAction(TranslationItem $translationItem)
    {
        $this->checkIsTranslator();

        $this->container
            ->get('claroline.translation.manager.translation_manager')
            ->clickUserLock($translationItem);

        return new JsonResponse();
    }

    /**              
     * @EXT\Route(
     *     "/{translationItem}/admin/lock", 
     *     name="claroline_translator_admin_lock",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function clickAdminLockAction(TranslationItem $translationItem)
    {
        $this->checkIsAdmin();

        $this->container
            ->get('claroline.translation.manager.translation_manager')
            ->clickAdminLock($translationItem);

        return new JsonResponse();
    }

    /**              
     * @EXT\Route(
     *     "/{translationItem}/forum/subject", 
     *     name="claroline_translator_forum_subject",
     *     options={"expose"=true}
     * )
     *
     * @return Response
     */
    public function getForumSubjectAction(TranslationItem $translationItem)
    {
        $om = $this->container->get('claroline.persistence.object_manager');
        $forumManager = $this->container->get('claroline.manager.forum_manager');

        if ($subject = $translationItem->getSubject()) {
           return new JsonResponse(array('subject_id' => $subject->getId()));
        }

        $categoryId = $this->container->get('claroline.config.platform_config_handler')
            ->getParameter('translator_category_id');

        $category = $om->getRepository('ClarolineForumBundle:Category')->find($categoryId);
        $user = $this->tokenStorage->getToken()->getUser();
        $subject = new Subject();
        $subject->setCreator($user);
        $subject->setAuthor($user->getFirstName() . ' ' . $user->getLastName());

        $title = strtoupper($translationItem->getLang()) . ': ' . $translationItem->getVendor() . $translationItem->getBundle() .  ' ' . $translationItem->getDomain() . $translationItem->getKey();
     /*   $content = $translator->trans(
            'current_translation', 
            array('%translation%' => $el->getTranslation()), 
            'translator'
        );*/
        $subject->setTitle($title);
        $subject->setCategory($category);
        $forumManager->createSubject($subject);
        /*$message = new Message();
        $message->setContent($content);
        $message->setCreator($user);
        $message->setAuthor($user->getFirstName() . ' ' . $user->getLastName());
        $message->setSubject($subject);
        $forumManager->createMessage($message, $subject);*/

        $translationItem->setSubject($subject);
        $om->persist($translationItem);
        $om->flush();

        return new JsonResponse(array('subject_id' => $subject->getId()));
    }


    /**
     * @SEC\PreAuthorize("canOpenAdminTool('platform_packages')")
     * @EXT\Route(
     *     "/admin/translator/form",
     *     name="claro_translator_admin_form"
     * )
     * @EXT\Template("ClarolineTranslatorBundle:Translator:adminOpen.html.twig")
     */
    public function adminOpenAction()
    {
        $category = $this->get('claroline.config.platform_config_handler')
            ->getParameter('translator_category_id');
        $allowUsers = $this->get('claroline.config.platform_config_handler')
            ->getParameter('translator_allow_users');
        $username = $this->get('claroline.config.platform_config_handler')
            ->getParameter('translator_git_username');

        $form = $this->get('form.factory')->create(new TranslatorType($category, $allowUsers, $username));

        return array('form' => $form->createView());
    }

    /**
     * @SEC\PreAuthorize("canOpenAdminTool('platform_packages')")
     * @EXT\Route(
     *     "/admin/translator/submit",
     *     name="claro_translator_admin_submit"
     * )
     * @EXT\Template("ClarolineTranslatorBundle:Translator:adminOpen.html.twig")
     */
    public function adminSubmitAction()
    {
        $form = $this->get('form.factory')->create(new TranslatorType());
        $form->handleRequest($this->get('request'));

        if ($form->isValid()) {
            $this->get('claroline.config.platform_config_handler')->setParameter('translator_category_id', $form->get('category')->getData());
            $this->get('claroline.config.platform_config_handler')->setParameter('translator_allow_users', $form->get('allowUsers')->getData());
            $this->get('claroline.config.platform_config_handler')->setParameter('translator_git_username', $form->get('git_username')->getData());

            return $this->redirect($this->generateUrl('claro_admin_plugins'));
        }

        return array('form' => $form->createView());
    }

    private function checkIsTranslator()
    {
        if (
            !$this->authorization->isGranted('ROLE_TRANSLATOR') && 
            !$this->authorization->isGranted('ROLE_TRANSLATOR_ADMIN')
        ) {
            throw new AccessDeniedException();
        }
    }

    public function checkIsAdmin()
    {
        if (!$this->authorization->isGranted('ROLE_TRANSLATOR_ADMIN')) {
            throw new AccessDeniedException();
        }
    }
}
