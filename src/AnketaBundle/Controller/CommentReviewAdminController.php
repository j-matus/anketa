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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Mapping\ClassMetadata;

class CommentReviewAdminController extends Controller {

    public function preExecute() {
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException();
        }
    }

    public function indexAction($page, $filter1, $filter2) {
        $em = $this->getDoctrine()->getManager();

        $active_season = $em->getRepository('AnketaBundle:Season')
                ->getActiveSeason();


        $first_result = (int)$page * 100;
        $next_page_id = (int)$page + 1;
        $prev_page_id = (int)$page - 1;

        $repo = $em->getRepository('AnketaBundle:Answer');
        $query = $repo->createQueryBuilder('q')
                ->where("q.comment <> ''")
                ->andWhere('q.season = :season');

        if ($filter1 != 'null') {
            $val = $filter1 === 'true' ? 1 : 0;
            $query = $query->andWhere('q.inappropriate = :inapp')
                        ->setParameter('inapp', $val);
        }
        if ($filter2 != 'null') {
            $val = $filter2 === 'true' ? 1 : 0;
            $query = $query->andWhere('q.reviewed = :review')
                        ->setParameter('review', $val);
        }
        $query = $query->setMaxResults(100)
                ->addOrderBy('q.id', 'ASC')
                ->setFirstResult($first_result)
                ->setParameter('season', $active_season)
                ->getQuery();

        $query = $query->setFetchMode('AnketaBundle\Entity\Answer',
                             'studyProgram', ClassMetadata::FETCH_EAGER);
        $query = $query->setFetchMode('AnketaBundle\Entity\Answer',
                             'subject', ClassMetadata::FETCH_EAGER);
        $query = $query->setFetchMode('AnketaBundle\Entity\Answer',
                             'teacher', ClassMetadata::FETCH_EAGER);
        $query = $query->setFetchMode('AnketaBundle\Entity\Answer',
                             'question', ClassMetadata::FETCH_EAGER);
        $query = $query->setFetchMode('AnketaBundle\Entity\Question',
                             'category', ClassMetadata::FETCH_EAGER);
        $results = $query->execute();
        $comments = array();
        foreach($results as $comment) {
            $section = StatisticsSection::getSectionOfAnswer($this->container, $comment);
            $comments[] = array($section, $comment);
        }
        return $this->render(
                'AnketaBundle:CommentReviewAdmin:index.html.twig',
                array('comments' => $comments,
                      'active_season' => $active_season,
                      'next_page_id' => $next_page_id,
                      'prev_page_id' => $prev_page_id,
                      'page_id' => $page,
                      'filter1' => $filter1,
                      'filter2' => $filter2));
    }

    /**
     * Processes POST requests from forms.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse>
     */
    public function processRequestAction() {
        $comments = $this->getRequest()->get('comment', null);
        $page = $this->getRequest()->get('page', null);
        $filter1 = $this->getRequest()->get('filter1', 'null');
        $filter2 = $this->getRequest()->get('filter2', 'null');
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
                                   array('page' => $page,
                                         'filter1' => $filter1,
                                         'filter2' => $filter2)));
    }
}
