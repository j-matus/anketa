<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Entity__Repository
 * @author     Jakub Marek <jakub.marek@gmail.com>
 */

namespace AnketaBundle\Entity;

use Doctrine\ORM\EntityRepository;
use AnketaBundle\Entity\StudyProgram;
use AnketaBundle\Entity\User;
use AnketaBundle\Entity\Subject;
use AnketaBundle\Entity\Season;

class StudyProgramRepository extends EntityRepository {

    public function getStudyProgrammesForUser(User $user, Season $season) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT sp
                           FROM AnketaBundle\\Entity\\UsersSubjects us,
                           AnketaBundle\\Entity\\StudyProgram sp
                           WHERE us.user = :user
                           AND us.studyProgram = sp
                           AND us.season = :season
                           ORDER BY sp.code ASC");
        $query->setParameter('user', $user);
        $query->setParameter('season', $season);

        return $query->getResult();
    }

    public function getFirstStudyProgrammeForUser(User $user, Season $season) {
        $result = $this->getStudyProgrammesForUser($user, $season);
        if (!empty($result[0])) return $result[0];
        else return null;
    }

    public function getStudyProgrammeAuthorizedUsers(StudyProgram $studyProgram, Season $season){
        $em = $this->getEntityManager();
        $users = $em->getRepository('AnketaBundle:User')->findUsersWithAnyRole(array("ROLE_STUDY_PROGRAMME_REPORT"));
        $authorizedUsers = array();
        foreach($users as $user){
            $studyProgrammes = $this->findByReportsUser($user);
            if(in_array($studyProgram, $studyProgrammes)) $authorizedUsers[] = $user;
        }
        return $authorizedUsers;
    }

    /**
     * @return StudyProgram
     */
    public function getStudyProgrammeForUserSubject(User $user, Subject $subject, Season $season) {
        $dql = 'SELECT sp FROM AnketaBundle\Entity\UsersSubjects us, AnketaBundle\Entity\StudyProgram sp ' .
                'WHERE us.user = :user AND us.subject = :subject AND us.season = :season AND us.studyProgram = sp';
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameters(array('user' => $user, 'subject' => $subject, 'season' => $season));
        return $query->getSingleResult();
    }

    /**
     * @return integer
     */
    public function getStudyYearForUserSubject($user, $subject, $season){
        $dql = 'SELECT us.studyYear FROM AnketaBundle\Entity\UsersSubjects us ' .
                'WHERE us.user = :user AND us.subject = :subject AND us.season = :season';
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameters(array('user' => $user, 'subject' => $subject, 'season' => $season));
        $result =  $query->getSingleResult();
        return $result['studyYear'];
    }

    public function getStudyYearForUser(User $user, Season $season, StudyProgram $sp = null) {
        $em = $this->getEntityManager();

        if($sp === null) return null;

        $query = $em->createQuery("SELECT us.studyYear
                           FROM AnketaBundle\\Entity\\UsersSubjects us,
                           AnketaBundle\\Entity\\StudyProgram sp
                           WHERE us.user = :user
                           AND us.studyProgram = :sp
                           AND us.season = :season
                           ORDER BY sp.code ASC")
                    ->setMaxResults(1);
        $query->setParameter('user', $user);
        $query->setParameter('season', $season);
        $query->setParameter('sp', $sp);

        $result =  $query->getSingleResult();

        if(count($result) == 0) return null;

        return $result['studyYear'];
    }

    public function findByReportsUser(User $user) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT sp
                           FROM AnketaBundle\\Entity\\UserStudyProgram usp,
                           AnketaBundle\\Entity\\StudyProgram sp
                           WHERE usp.user = :user
                           AND usp.studyProgram = sp
                           ORDER BY sp.name, sp.code ASC");
        $query->setParameter('user', $user);

        return $query->getResult();
    }

    public function getAllWithAnswers(Season $season, $orderByName = false) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT DISTINCT sp
                           FROM AnketaBundle\\Entity\\StudyProgram sp,
                           AnketaBundle\\Entity\\Answer a,
                           AnketaBundle\\Entity\\Category c,
                           AnketaBundle\\Entity\\Question q
                           WHERE sp.id = a.studyProgram
                           AND a.teacher IS NULL
                           AND a.subject IS NULL
                           AND a.season = :season
                           AND ((a.option IS NOT NULL) OR (a.comment IS NOT NULL))
                           AND a.question = q
                           AND q.category = c
                           AND c.type = :category_type
                           ORDER BY " . ($orderByName ? "sp.name, " : "") . "sp.code ASC");
        $query->setParameter('season', $season);
        $query->setParameter('category_type', CategoryType::STUDY_PROGRAMME);
        return $query->getResult();
    }
    
    public function countForSeason(Season $season) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT COUNT(DISTINCT sp)
                           FROM AnketaBundle\\Entity\\StudyProgram sp,
                           AnketaBundle\\Entity\\Answer a,
                           AnketaBundle\\Entity\\Category c,
                           AnketaBundle\\Entity\\Question q
                           WHERE sp.id = a.studyProgram
                           AND a.teacher IS NULL
                           AND a.subject IS NULL
                           AND a.season = :season
                           AND ((a.option IS NOT NULL) OR (a.comment IS NOT NULL))
                           AND a.question = q
                           AND q.category = c
                           AND c.type = :category_type");
        $query->setParameter('season', $season);
        $query->setParameter('category_type', CategoryType::STUDY_PROGRAMME);
        return $query->getSingleScalarResult();
    }

}
