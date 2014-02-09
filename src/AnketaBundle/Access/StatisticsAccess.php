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

use AnketaBundle\Entity\Response;
use AnketaBundle\Entity\Season;

class StatisticsAccess extends AbstractAccess
{
    /**
     * Returns whether the current user is teacher in the specified season.
     *
     * @param Season $season
     * @return boolean
     */
    public function isATeacher(Season $season) {
        $userSeasonRepo = $this->em->getRepository('AnketaBundle:UserSeason');
        $userSeason = $userSeasonRepo->findOneBy(array('season' => $season, 'user' => $this->getUser()));
        if ($userSeason === NULL) {
            return false;
        }
        return $userSeason->getIsTeacher();
    }

    /**
     * Returns whether the current user can or could have created comments,
     * and thus should see a "My comments" item in the menu.
     *
     * @param Season $season
     * @return boolean
     */
    public function hasOwnResponses(Season $season) {
        return $this->isATeacher($season);
    }

    /**
     * Returns whether the current user has taught some subjects, and thus
     * should see a "My subjects" item in the menu.
     *
     * @param Season $season
     * @return boolean
     */
    public function hasOwnSubjects(Season $season) {
        return $this->isATeacher($season);
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
    public function canSeeTopLevelResults()
    {
        $activeSeason = $this->em->getRepository('AnketaBundle:Season')->getActiveSeason();
        if ($activeSeason->getFafRestricted() && $this->hasOwnSubjects($activeSeason)) return true;

        $seasons = $this->em->getRepository('AnketaBundle:Season')->findAll();
        foreach ($seasons as $season) {
            if ($this->canSeeResults($season)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Returns whether the active season has visible results based on the
     * specified minimal permission.
     *
     * @param int $min_permisson minimal permission the season has to have
     * @return boolean
     */
    public function canSeeActiveSeasonResults($min_permisson = Season::PERMISSION_NUMERIC)
    {
        $activeSeason = $this->em->getRepository('AnketaBundle:Season')->getActiveSeason();
        return $this->canSeeResults($activeSeason, $min_permisson);
    }

    /**
     * Returns whether the current user can view results of the given season.
     *
     * @param Season $season
     * @return boolean
     */
    public function canSeeResults(Season $season, $min_permisson = Season::PERMISSION_NUMERIC)
    {
        if ($this->security->isGranted('ROLE_ADMIN')) return TRUE;
        if ($season->getFafRestricted() && $this->hasOwnSubjects($season)) return TRUE;

        // public
        if ($this->getUser() === NULL) {
            return $season->getPermissionsPublic() >= $min_permisson;
        }
        // university
        if (!$this->isUserFromFaculty()) {
            return $season->getPermissionsUniversity() >= $min_permisson;
        } 
        // faculty
        return $season->getPermissionsFaculty() >= $min_permisson;
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
    public function commentsBlocked(Season $season)
    {
        return !$this->canSeeResults($season, Season::PERMISSION_NUMERIC_TEXT);
    }

    /**
     * Returns whether the current user can respond to results of the given
     * season.
     *
     * @param Season $season
     * @return boolean
     */
    public function canCreateResponses(Season $season) {
        return $this->canSeeResults($season) && $this->hasOwnResponses($season) && $season->getRespondingOpen();
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
        return $user && $user->getId() === $response->getAuthor()->getId() && $response->getSeason()->getRespondingOpen();
    }

    /**
     * Returns whether the current user can view responses to results in the
     * given season.
     *
     * @param Season $season
     * @return boolean
     */
    public function canSeeResponses(Season $season) {
        return $this->canSeeResults($season, Season::PERMISSION_NUMERIC_TEXT_RESPONSES) || $this->canCreateResponses($season);
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
        if ($this->security->isGranted('ROLE_ALL_REPORTS')) {
            $repository = $this->em->getRepository('AnketaBundle:Department');
            return $repository->findBy(array(), array('name' => 'ASC'));
        }
        else if ($this->security->isGranted('ROLE_DEPARTMENT_REPORT')) {
            $user = $this->getUser();
            $userSeasons = $this->em->getRepository('AnketaBundle:UserSeason')->findBy(array('user' => $user));
            $departments = array();
            foreach ($userSeasons as $userSeason) {
                if ($userSeason->getDepartment()) {
                    $departments[] = $userSeason->getDepartment();
                }
            }
            return $departments;
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
            $all = $repository->getAllWithAnswers($season, true);
            foreach ($all as $program) $ids[$program->getId()] = true;
            $allowed = $repository->findByReportsUser($this->getUser());
            $intersection = array();
            foreach ($allowed as $program) {
                if (isset($ids[$program->getId()])) $intersection[] = $program;
            }
            return $intersection;
        }
        else {
            return array();
        }
    }

}
