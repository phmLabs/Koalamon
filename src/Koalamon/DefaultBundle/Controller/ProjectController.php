<?php

namespace Koalamon\DefaultBundle\Controller;

use Bauer\IncidentDashboard\CoreBundle\Controller\ProjectAwareController;
use Bauer\IncidentDashboard\CoreBundle\Entity\Project;
use Bauer\IncidentDashboard\CoreBundle\Entity\System;
use Bauer\IncidentDashboard\CoreBundle\Entity\Translation;
use Bauer\IncidentDashboard\CoreBundle\Entity\UserRole;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProjectController extends ProjectAwareController
{
    public function adminAction()
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);
        return $this->render('KoalamonDefaultBundle:Project:admin.html.twig', array('roles' => UserRole::getRoles()));
    }

    
    public function createAction(Request $request)
    {
        $project = new Project();

        $form = $this->createFormBuilder($project)
            ->add('identifier', 'text', array('attr' => array('tooltip' => 'gift.tooltip.amount')))
            ->add('name', 'text')
            ->add('save', 'submit', array('label' => 'Create Project'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {

            $existingProject = $this->getDoctrine()
                ->getRepository('BauerIncidentDashboardCoreBundle:Project')
                ->findOneBy(['identifier' => $project->getIdentifier()]);

            if ($existingProject !== NULL) {
                $this->addFlash('notice', 'Project identifier already exists');
                return $this->render('KoalamonDefaultBundle:Project:create.html.twig', array('form' => $form->createView()));
            }

            $em = $this->getDoctrine()->getManager();

            $project->setOwner($this->getUser());

            $project->setLastStatusChange(new \DateTime());


            $em->persist($project);
            $em->flush();

            $userRole = new UserRole($this->getUser(), $project, UserRole::ROLE_OWNER);

            $em->persist($userRole);
            $em->flush();

            return $this->redirectToRoute('bauer_incident_dashboard_core_homepage', array('project' => $project->getIdentifier()));
        }

        return $this->render('KoalamonDefaultBundle:Project:create.html.twig', array('form' => $form->createView()));
    }


    public function removeCollaboratorAction(Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $userId = $request->get("userId");

        $user = $this->getDoctrine()
            ->getRepository('BauerIncidentDashboardCoreBundle:User')
            ->find($userId);

        $currentUserRole = $this->getUser()->getUserRole($this->getProject());

        if (!is_null($user) && $currentUserRole->getRole() < $user->getUserRole($this->getProject())->getRole()) {
            $em = $this->getDoctrine()->getManager();

            $userRoles = $this->getDoctrine()
                ->getRepository('BauerIncidentDashboardCoreBundle:UserRole')
                ->findBy(array("project" => $this->getProject(), 'user' => $user));

            foreach ($userRoles as $userRole) {
                $em->remove($userRole);
            }

            $em->flush();
        }

        return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
    }


    public function addCollaboratorAction(Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $userId = $request->get("userId");
        $role = $request->get("role");

        $user = $this->getDoctrine()
            ->getRepository('BauerIncidentDashboardCoreBundle:User')
            ->find($userId);

        $currentUserRole = $this->getUser()->getUserRole($this->getProject());

        if ($currentUserRole->getRole() >= $role) {
            throw new AccessDeniedException('You are not allowed to call this action');
        }

        if ($currentUserRole->getRole() >= $user->getUserRole($this->getProject())->getRole()) {
            throw new AccessDeniedException('You are not allowed to call this action');
        }

        if (!is_null($user)) {
            $em = $this->getDoctrine()->getManager();

            // only one role allowed
            $userRoles = $this->getDoctrine()
                ->getRepository('BauerIncidentDashboardCoreBundle:UserRole')
                ->findBy(array("project" => $this->getProject(), 'user' => $user));

            foreach ($userRoles as $userRole) {
                $em->remove($userRole);
            }

            $em->flush();

            $userRole = new UserRole($user, $this->getProject(), $role);
            $em->persist($userRole);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteTranslationAction(Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $translationObject = $this->getDoctrine()
            ->getRepository('BauerIncidentDashboardCoreBundle:Translation')
            ->find($request->get("translation_id"));

        $em = $this->getDoctrine()->getManager();
        $em->remove($translationObject);
        $em->flush();

        $this->addFlash('success', 'Translation successfully deleted.');
        return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function storeTranslationAction(Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $translation = $request->get("translation");

        if (array_key_exists("id", $translation)) {
            $translationObject = $this->getDoctrine()
                ->getRepository('BauerIncidentDashboardCoreBundle:Translation')
                ->find($translation["id"]);
        } else {
            $translationObject = new Translation();
            $translationObject->setProject($this->getProject());
        }

        if ($translation["identifier"] != "") {
            $translationObject->setIdentifier($translation["identifier"]);
        } else {
            $this->addFlash('notice', 'The parameter "identifier" is required');
            return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
        }

        if ($translation["message"] != "") {
            $translationObject->setMessage($translation["message"]);
        } else {
            $translationObject->setMessage(null);
        }

        if ($translation["system"] != "") {
            $translationObject->setSystem($translation["system"]);
        } else {
            $translationObject->setSystem(null);
        }

        if ($translation["type"] != "") {
            $translationObject->setType($translation["type"]);
        } else {
            $translationObject->setType(null);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($translationObject);
        $em->flush();

        $this->addFlash('success', 'Translation "' . $translationObject->getIdentifier() . '" successfully saved.');
        return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function storeSystemAction(Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);
        $system = $request->get('system');

        if (array_key_exists("id", $system)) {
            $systemObject = $this->getDoctrine()
                ->getRepository('BauerIncidentDashboardCoreBundle:System')
                ->find($system["id"]);

        } else {
            $systemObject = new System();
            $systemObject->setProject($this->getProject());
        }

        if ($system["identifier"] != "") {
            $systemObject->setIdentifier($system["identifier"]);
        } else {
            $this->addFlash('notice', 'The parameter "identifier" is required');
            return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
        }

        if ($system["url"] != "" && !filter_var($system['url'], FILTER_VALIDATE_URL) === false) {
            $systemObject->setUrl($system["url"]);
        } else {
            $this->addFlash('notice', 'The parameter "URL" requires a valid URL');
            return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
        }

        if ($system["name"] != "") {
            $systemObject->setName($system["name"]);
        } else {
            $systemObject->setName($system['url']);
        }

        if ($system["description"] != "") {
            $systemObject->setDescription($system["description"]);
        } else {
            $systemObject->setDescription(null);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($systemObject);
        $em->flush();

        $this->addFlash('success', 'System "' . $systemObject->getName() . '" successfully saved.');
        return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteSystemAction(Request $request)
    {
        $this->assertUserRights(UserRole::ROLE_ADMIN);

        $systemObject = $this->getDoctrine()
            ->getRepository('BauerIncidentDashboardCoreBundle:System')
            ->find($request->get("system_id"));

        $em = $this->getDoctrine()->getManager();
        $em->remove($systemObject);
        $em->flush();

        $this->addFlash('success', 'System "' . $systemObject->getName() . '" deleted.');
        return $this->redirect($this->generateUrl('koalamon_default_project_admin', array("project" => $this->getProject()->getIdentifier())));
    }

}
