<?php

/**
 * @copyright Copyright (c) 2015 The FMFI Anketa authors (see AUTHORS).
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

class CommentReviewAdminController extends Controller {

    public function preExecute() {
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
    }

    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $active_season = $em->getRepository('AnketaBundle:Season')
                ->getActiveSeason();

        $first_result = 0;
        $page_id = $this->getRequest()->get('page', null);
        if ($page_id != null) {
               $first_result = (int)$page_id * 100;
        }
        $next_page_id = (int)$page_id + 1;
        $prev_page_id = (int)$page_id - 1;

        $criteria = new \Doctrine\Common\Collections\Criteria();
        $repo = $em->getRepository('AnketaBundle:Answer');
        $query = $repo->createQueryBuilder('q')
                ->where("q.comment <> ''")
                ->andWhere("q.season = :season")
                ->addOrderBy("q.inappropriate", 'DESC')
                ->setMaxResults(100)
                ->setFirstResult($first_result)
                ->setParameter('season', $active_season)
                ->getQuery();

        $comments = $query->execute();
        return $this->render(
                'AnketaBundle:CommentReviewAdmin:index.html.twig',
                array('comments' => $comments,
                      'active_season' => $active_season,
                      'next_page_id' => $next_page_id,
                      'prev_page_id' => $prev_page_id,
                      'page_id' => $page_id));
    }

    /**
     * Processes POST requests from forms.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse>
     */
    public function processRequestAction() {
        $comments = $this->getRequest()->get('comment', null);
        $page = $this->getRequest()->get('page', null);
        if ($comments == null) {
            return new Response('Required parameter "comment" is missing.', 400);
        }
        if ($page == null) {
            return new Response('Required parameter "page" is missing.', 400);
        }

        $em = $this->getDoctrine()->getManager();
        $answers = $em->getRepository('AnketaBundle\Entity\Answer');
        foreach ($comments as $id => $comment) {
            if (!isset($comment['inappropriate'])) {
                $comment['inappropriate'] = 0; 
            }
            if (!isset($comment['reviewed'])) {
                $comment['reviewed'] = 0; 
            }

            $inapp = $comment['inappropriate'];
            $reviewed = $comment['reviewed'];
            $prev_inapp = $comment['prev_inappropriate'];
            $prev_reviewed = $comment['prev_reviewed'];
            if ($inapp != $prev_inapp) {
                $answer = $answers->findOneBy(array('id' => $id));
                $answer->setInappropriate($inapp);
                $answer->setReviewed($inapp);

                $em->flush();
            } else if ($reviewed != $prev_reviewed) {
                $answer = $answers->findOneBy(array('id' => $id));
                $answer->setReviewed($reviewed);
                 
                $em->flush();
            }
        }
        return $this->redirect(
                $this->generateUrl('admin_comments_review',
                                   array('page' => $page)));
    }

    /**
     * Links the teacher with the subject as reported in particular
     * TeachingAssociation.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function addTeacherToSubject(TeachingAssociation $ta) {
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
}
