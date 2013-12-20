<?php

/**
 * @copyright Copyright (c) 2013 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Controller
 *
 */

namespace AnketaBundle\Controller;

use Doctrine\DBAL\DBALException;

use AnketaBundle\Entity\TeachersSubjects;
use AnketaBundle\Entity\User;
use AnketaBundle\Entity\TeachingAssociation;
use AnketaBundle\Entity\UserSeason;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;

class TeachingAssociationAdminController extends Controller
{
    // TODO protect /admin at one place
    public function preExecute()
    {
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
    }

    // TODO pagination
    // TODO kontrola ci ziadane priradenia v reportoch uz neexistuju, to plati
    //      aj pre pripad, ze uz sa priradenie vytvori pomocou funkcie
    //      addTeacherToSubject();ak uz pripojenie exisuje nezobrazovat tlacidlo
    // TODO group by (subjec,t teacher, season), zobrazit pocet rovnakych
    //      cez COUNT() a spojit notes do jedneho, aby to vyzeralo ako 1 ticket
    // TODO CSFR
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $active_season = $em->getRepository('AnketaBundle:Season')
                ->getActiveSeason();

        $tas = $em->getRepository('AnketaBundle:TeachingAssociation')
                ->findBy(array('season' => $active_season,
                               'completed' => false),
                         array('createdOn' => 'ASC'));

        return $this->render(
                'AnketaBundle:TeachingAssociationAdmin:index.html.twig',
                 array('tas' => $tas, 'active_season' => $active_season));
    }

    /**
     * Processes POST requests from forms.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse>
     */
    public function processRequestAction()
    {
        // check if the record exists
        $ta_id = $this->getRequest()->get('ta_id', null);
        if ($ta_id == null) {
            return new Response('Required parameter "ta_id" is missing.', 400);
        }
        $em = $this->getDoctrine()->getManager();
        $ta = $em->getRepository('AnketaBundle:TeachingAssociation')
                ->find($ta_id);
        if ($ta == null) {
            return new Response('Record was not found.', 400);
        }

        $approve = $this->getRequest()->get('approve', null);
        if ($approve !== null) {
            return $this->addTeacherToSubject($ta);
        }

        $mark_as_completed = $this->getRequest()->get('mark-as-completed', null);
        if ($mark_as_completed !== null) {
            return $this->markAsCompleted($ta);
        }

        $delete = $this->getRequest()->get('delete', null);
        if ($delete !== null) {
            $em->remove($ta);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success', 'Záznam bol úspešne zmazaný.');
        }

        return $this->redirect($this->generateUrl(
                'admin_teaching_associations'));
    }

    /**
     * Links the teacher with the subject as reported in particular
     * TeachingAssociation.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function addTeacherToSubject(TeachingAssociation $ta)
    {
        $em = $this->getDoctrine()->getManager();

        // at least one of the functions should to be set to 1 (true)
        $is_function_set = $ta->getTrainer() || $ta->getTeacher();

        if ($ta->getTeacher() !== null && $ta->getSubject() !== null
                && $ta->getSeason() !== null && $is_function_set) {

            // check for duplication
            $userSeason = $em->getRepository('AnketaBundle:UserSeason')
                    ->findOneBy(array('season' => $ta->getSeason(),
                                      'user'   => $ta->getTeacher()));
            if ($userSeason === null) {
                $userSeason = new UserSeason();
                $userSeason->setIsStudent(false);
                $userSeason->setIsTeacher(true);
                $userSeason->setSeason($ta->getSeason());
                $userSeason->setUser($ta->getTeacher());
            } else {
                $userSeason->setIsTeacher(true);
            }
            $em->persist($userSeason);
            $em->flush();

            // link the teacher with the subject
            $teachersSubjects = new TeachersSubjects($ta->getTeacher(),
                    $ta->getSubject(), $ta->getSeason());

            $teachersSubjects->setLecturer($ta->getLecturer());
            $teachersSubjects->setTrainer($ta->getTrainer());

            $session = $this->get('session');

            try {
                $em->persist($teachersSubjects);
                $em->flush();
            } catch (DBALException $e) {
                // TODO check if $e really says the insert is duplicated (SQL 23000)
                $session->getFlashBag()
                        ->add('error', 'Učiteľ už je priradený k predmetu.');

                return $this->redirect($this->generateUrl(
                        'admin_teaching_associations'));
            }

            $session->getFlashBag()
                    ->add('success', 'Učiteľ bol úspešne priradený k predmetu.');

            return $this->redirect($this->generateUrl(
                    'admin_teaching_associations'));
        }
    }

    /**
     * Marks a request from TeachingAssociation as completed and sends email
     * to the user who requested it.
     * If there are more requests with equal season, teacher and subjects, it
     * marks them as completed as well.
     *
     * @param  TeachingAssociation $ta
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function markAsCompleted(TeachingAssociation $ta)
    {
        $em = $this->getDoctrine()->getManager();

        $ta->setCompleted(true);
        $em->persist($ta);
        $em->flush();

        if ($ta->getRequestedBy() !== null)
            $this->sendEmailAfterApproval($ta);

        // mark as completed all other uncompleted requests with the same
        // subject and teacher and send email to all requestedBy users
        $tas = $em->getRepository('AnketaBundle:TeachingAssociation')->findBy(
                array('subject' => $ta->getSubject(),
                      'teacher' => $ta->getTeacher(),
                      'season' => $ta->getSeason(),
                      'completed' => false
                        ));

        foreach ($tas as $ta_item) {
            $ta_item->setCompleted(true);
            $em->persist($ta_item);
            $em->flush();

            if ($ta->getRequestedBy() !== null)
                $this->sendEmailAfterApproval($ta_item);
        }

        $this->get('session')->getFlashBag()
                ->add('success', 'Žiadosť bola označená za vybavenú a email bol odoslaný nahlasovateľom.');

        return $this->redirect($this->generateUrl(
                'admin_teaching_associations'));
    }

    /**
     * Sends an email to the user about the successful completion of his/her
     * request.
     *
     * @param TeachingAssociation $ta
     */
    private function sendEmailAfterApproval(TeachingAssociation $ta)
    {
        $skratkaFakulty = $this->container->getParameter('skratka_fakulty');

        $subject = 'Požiadavka na zmenu učiteľa bola vybavená';
        $to = $ta->getRequestedBy()->getLogin().'@uniba.sk';
        $sender = $this->container->getParameter('mail_sender');
        $replyTo = $this->container->getParameter('mail_replyto_new_teaching_association');

        $body = $this->renderView('AnketaBundle:TeachingAssociationAdmin:email.txt.twig',
                array('teachingAssociation' => $ta));

        $this->get('mailer'); // DO NOT DELETE THIS LINE
        // it autoloads required things before Swift_Message can be used

        $message = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom($sender)
        ->setTo($to)
        ->setReplyTo($replyTo)
        ->setBody($body);
        $this->get('mailer')->send($message);
    }
}
