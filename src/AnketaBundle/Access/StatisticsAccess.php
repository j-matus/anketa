<?php
/**
 * @copyright Copyright (c) 2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Access
 */

namespace AnketaBundle\Access;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContextInterface;
use AnketaBundle\Entity\Response;
use AnketaBundle\Entity\Season;

class StatisticsAccess
{
    /** @var SecurityContextInterface */
    private $security;

    /** @var EntityManager */
    private $em;

    /** @var mixed */
    private $user;

    public function __construct(SecurityContextInterface $security, EntityManager $em) {
        $this->security = $security;
        $this->em = $em;
        $this->user = null;
    }

    /**
     * Returns the logged in user, or null if nobody is logged in.
     *
     * @return mixed
     */
    public function getUser() {
        if ($this->user === null && $this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $token = $this->security->getToken();
            if ($token) $this->user = $token->getUser();
        }
        return $this->user;
    }

    /**
     * Returns whether the current user can or could have created comments,
     * and thus should see a "My comments" item in the menu.
     *
     * @return boolean
     */
    public function hasOwnResponses() {
        return $this->security->isGranted('ROLE_TEACHER');
    }

    /**
     * Returns whether the current user has taught some subjects, and thus
     * should see a "My subjects" item in the menu.
     *
     * @return boolean
     */
    public function hasOwnSubjects() {
        return $this->security->isGranted('ROLE_TEACHER');
    }

    /**
     * Returns whether the current user should be able to see results even
     * when the number of votes is under the threshold.
     *
     * @return boolean
     */
    public function hasFullResults() {
        return $this->security->isGranted('ROLE_FULL_RESULTS');
    }

    /**
     * Returns whether a season exists that has visible results (and thus
     * the top-level "Results" section should be shown).
     *
     * @return boolean
     */
    public function canSeeTopLevelResults() {
        return $this->em->getRepository('AnketaBundle:Season')->getTopLevelResultsVisible();
    }

    /**
     * Returns whether the active season has visible results.
     *
     * @return boolean
     */
    public function activeSeasonHasVisibleResults() {
        $activeSeason = $this->em->getRepository('AnketaBundle:Season')->getActiveSeason();
        return $activeSeason->getResultsVisible();
    }

    /**
     * Returns whether the current user can view results of the given season.
     *
     * @param Season $season
     * @return boolean
     */
    public function canSeeResults(Season $season) {
        if ($this->security->isGranted('ROLE_ADMIN')) return true;
        return $season->getResultsVisible() && ($season->getResultsPublic() || ($this->getUser() !== null));
    }

    /**
     * Returns whether the current user is blocked from seeing comments of
     * the given season, and should be shown a prompt to log in instead.
     * (This only comes into effect if the user can see the results at all.
     * That is not checked by this function.)
     *
     * @param Season $season
     * @return boolean
     */
    public function commentsBlocked(Season $season) {
        return !$this->getUser();
    }

    /**
     * Returns whether the current user can respond to results of the given
     * season.
     *
     * @param Season $season
     * @return boolean
     */
    public function canCreateResponses(Season $season) {
        return $this->canSeeResults($season) && $this->hasOwnResponses() && $season->getRespondingOpen();
    }

    /**
     * Returns whether the current user can edit the given response.
     *
     * @param \AnketaBundle\Entity\Response $response
     * @return boolean
     */
    public function canEditResponse(Response $response) {
        if (!$this->canSeeResults($response->getSeason())) return false;
        $user = $this->getUser();
        return $user && $user->getUserName() === $response->getAuthorLogin() && $response->getSeason()->getRespondingOpen();
    }

    /**
     * Returns whether the current user can view responses to results in the
     * given season.
     *
     * @param Season $season
     * @return boolean
     */
    public function canSeeResponses(Season $season) {
        return $this->canSeeResults($season) && ($season->getResponsesVisible() || $this->canCreateResponses($season));
    }

    /**
     * Returns whether the current user can view some reports, and thus should
     * see a "My reports" item in the menu.
     *
     * @return boolean
     */
    public function hasReports() {
        return $this->security->isGranted('ROLE_ALL_REPORTS') ||
            $this->security->isGranted('ROLE_DEPARTMENT_REPORT') ||
            $this->security->isGranted('ROLE_STUDY_PROGRAMME_REPORT');
    }

    /**
     * Returns the departments that the current user can view reports of.
     *
     * @param Season $season
     * @return array(\AnketaBundle\Entity\Department)
     */
    public function getDepartmentReports(Season $season) {
        $repository = $this->em->getRepository('AnketaBundle:Department');
        if ($this->security->isGranted('ROLE_ALL_REPORTS')) {
            return $repository->findBy(array(), array('name' => 'ASC'));
        }
        else if ($this->security->isGranted('ROLE_DEPARTMENT_REPORT')) {
            return $repository->findByUser($this->getUser(), $season);
        }
        else {
            return array();
        }
    }

    /**
     * Returns the study programmes that the current user can view reports of.
     *
     * @param Season $season
     * @return array(\AnketaBundle\Entity\StudyProgram)
     */
    public function getStudyProgrammeReports(Season $season) {
        $repository = $this->em->getRepository('AnketaBundle:StudyProgram');
        if ($this->security->isGranted('ROLE_ALL_REPORTS')) {
            return $repository->getAllWithAnswers($season, true);
        }
        else if ($this->security->isGranted('ROLE_STUDY_PROGRAMME_REPORT')) {
            return $repository->findByReportsUser($this->getUser(), $season);
        }
        else {
            return array();
        }
    }

}
