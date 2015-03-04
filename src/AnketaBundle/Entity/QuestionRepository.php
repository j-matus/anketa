<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Entity__Repository
 * @author     Jakub Markoš <jakub.markos@gmail.com>
 * @author     Martin Králik <majak47@gmail.com>
 */

namespace AnketaBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\EntityRepository;
use AnketaBundle\Entity\Category;
use AnketaBundle\Entity\CategoryType;
use AnketaBundle\Entity\User;
use AnketaBundle\Lib\Preconditions;

class QuestionRepository extends EntityRepository {

    public function getQuestion($id) {
        $dql = 'SELECT q, o FROM AnketaBundle\Entity\Question q '.
               'INNER JOIN q.options o WHERE q.id = :id';

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('id', $id);

        return $query->getSingleResult();
    }

    /**
     *
     * @param Category $category category of the questions to look for
     * @param Season $season active season
     * @return ArrayCollection questions ordered by position
     */
    public function getOrderedQuestions(Category $category, Season $season) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT q, o
                                   FROM AnketaBundle\Entity\Question q
                                   LEFT JOIN q.options o
                                   WHERE q.category = :category
                                   AND q.season = :season
                                   ORDER BY q.position ASC");
        $query->setParameter('category', $category);
        $query->setParameter('season', $season);
        return $query->getResult();
    }

    //TODO(ppershing): Toto je cele zle.
    //                 Nefunguje to pre "general" kategoriu,
    //                 kde to vrati pocet otazok iba prvej kategorie.
    //                 Navrhujem tuto funkciu zrusit a pouzivat iba tu nad nou.
    public function getOrderedQuestionsByCategoryType($type, Season $season) {
        Preconditions::check(CategoryType::isValid($type));
        $category = $this->getEntityManager()
                ->getRepository('AnketaBundle\Entity\Category')
                ->findOneBy(array('type' => $type));
        if ($category == null) {
            throw new NoResultException();
        }
        return $this->getOrderedQuestions($category, $season);
    }

    public function getNumberOfQuestionsForCategoryType($type, Season $season) {
        Preconditions::check(CategoryType::isValid($type));
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT COUNT(q.id) as questions
                                   FROM AnketaBundle\Entity\Question q
                                   JOIN q.category c
                                   WHERE c.type = :type
                                   AND q.season = :season");
        $query->setParameter('type', $type);
        $query->setParameter('season', $season);
        $result = $query->getResult();
        return $result[0]['questions'];
    }

    public function getNumberOfQuestionsForCategory(Category $category, Season $season) {
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT COUNT(q.id) as questions
                                   FROM AnketaBundle\Entity\Question q
                                   WHERE q.category = :category
                                   AND q.season = :season");
        $query->setParameter('category', $category);
        $query->setParameter('season', $season);
        $result = $query->getResult();
        return $result[0]['questions'];
    }

    /**
     * Returned array contains progress for provided user in following format:
     * - result[subject_id]['answered'] = number of answers for subject
     * - result[subject_id]['total'] = number of questions for subject
     * Includes progress for teacher with the largest progress!
     * @param User $user
     * @param Season $season
     * @return array
     */
    public function getProgressForSubjectsByUser(User $user, Season $season) {
        $em = $this->getEntityManager();
        // TODO: tu sa uz da pouzit IDENTITY(a.subject) namiesto s.id a joinu
        $query = $em->createQuery('SELECT s.id AS subject_id, COUNT(a.id) AS num
                                   FROM AnketaBundle\Entity\Answer a
                                   JOIN a.subject s
                                   WHERE a.author = :author
                                         AND a.season = :season
                                         AND (a.option IS NOT NULL OR a.comment IS NOT NULL)
                                         AND a.teacher IS NULL
                                   GROUP BY s.id');
        $query->setParameter('author', $user);
        $query->setParameter('season', $season);
        $rows = $query->getResult();

        $subjectQuestions = $this->getNumberOfQuestionsForCategoryType(CategoryType::SUBJECT, $season);
        $subjectTeachersQuestions = $this->getNumberOfQuestionsForCategoryType(CategoryType::TEACHER_SUBJECT, $season);

        $teachers = $this->getProgressForSubjectTeachersByUser($user, $season);

        $mostCompleteTeacher = function(array $array)
        {
            return \max(\array_map(function($value){return $value['answered'];}, $array));
        };

        $subjectRepository = $em->getRepository('AnketaBundle:Subject');

        $result = array();
        foreach ($subjectRepository->getAttendedSubjectsForUser($user, $season) as $subject) {
                $result[$subject->getId()] = array(
                    'answered' => 0,
                    'total' => $subjectQuestions
                );
        }
        foreach ($rows as $row) {
            $result[$row['subject_id']]['answered'] = $row['num'];
            if (array_key_exists($row['subject_id'], $teachers)) {
                $result[$row['subject_id']]['answered'] += $mostCompleteTeacher($teachers[$row['subject_id']]);
                $result[$row['subject_id']]['total'] += $subjectTeachersQuestions;
            }
        }

        return $result;
    }

    /**
     * Returned array contains progress for provided user in following format:
     * - result[subject_id][teacher_id]['answered'] = number of answers for subject and teacher
     * - result[subject_id][teacher_id]['total'] = number of questions for subject and teacher
     * @param User $user
     * @param Season $season
     * @return array
     */
    public function getProgressForSubjectTeachersByUser(User $user, Season $season) {
        $em = $this->getEntityManager();
        // TODO: Tu sa uz da pouzit IDENTITY(a.subject) namiesto s.id a joinu
        $query = $em->createQuery('SELECT s.id AS subject_id, t.id AS teacher_id, COUNT(a.id) AS num
                                   FROM AnketaBundle\Entity\Answer a
                                        JOIN a.subject s
                                        JOIN a.teacher t
                                   WHERE a.author = :author
                                         AND a.season = :season
                                         AND (a.option IS NOT NULL OR a.comment IS NOT NULL)
                                   GROUP BY s.id, a.teacher');
        $query->setParameter('author', $user);
        $query->setParameter('season', $season);
        $rows = $query->getResult();

        $subjectTeachersQuestions = $this->getNumberOfQuestionsForCategoryType(CategoryType::TEACHER_SUBJECT, $season);

        $subjectRepository = $em->getRepository('AnketaBundle:Subject');
        $teacherSubjectRepository = $em->getRepository('AnketaBundle:TeachersSubjects');
        $result = array();
        $subjects = $subjectRepository->getAttendedSubjectsForUser($user, $season);
        foreach ($subjects as $subject) {
            $teachersSubjects = $teacherSubjectRepository->findBy(array('subject' => $subject->getId(), 'season' => $season->getId()));
            foreach ($teachersSubjects as $teacherSubject) {
                $result[$subject->getId()][$teacherSubject->getTeacher()->getId()] = array(
                    'answered' => 0,
                    'total' => $subjectTeachersQuestions
                );
            }
        }

        foreach ($rows as $row) {
            $result[$row['subject_id']][$row['teacher_id']]['answered'] = $row['num'];
        }

        return $result;
    }

    /**
     * Returned array contains progress for provided user in following format:
     * - result[cat_id]['answered'] = number of answers for category
     * - result[cat_id]['total'] = number of questions for category
     * @param User $user
     * @param Season $season
     * @return array
     */
    public function getProgressForCategoriesByUser(User $user, Season $season) {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT c.id AS cat_id, COUNT(a.id) AS num
                                   FROM AnketaBundle\Entity\Answer a
                                        JOIN a.question q
                                        JOIN q.category c
                                   WHERE a.author = :author
                                         AND a.season = :season
                                         AND (a.option IS NOT NULL OR a.comment IS NOT NULL)
                                   GROUP BY c.id');
        $query->setParameter('author', $user);
        $query->setParameter('season', $season);
        $rows = $query->getResult();

        $result = array();
        foreach ($em->getRepository('AnketaBundle\Entity\Category')->findAll() as $category) {
                $result[$category->getId()] = array(
                    'answered' => 0,
                    'total' => $this->getNumberOfQuestionsForCategory($category, $season)
                );
        }

        foreach ($rows as $row) {
            $result[$row['cat_id']]['answered'] = $row['num'];
        }

        return $result;
    }

/**
     * Returned array contains progress for provided user in following format:
     * - result[studyprogram_code]['answered'] = number of answers for subject and teacher
     * - result[studyprogram_code]['total'] = number of questions for subject and teacher
     * @param User $user
     * @param Season $season
     * @return array
     */
    public function getProgressForStudyProgramsByUser(User $user, Season $season) {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT sp.code AS sp_code, COUNT(a.id) AS num
                                   FROM AnketaBundle\Entity\Answer a
                                        JOIN a.studyProgram sp
                                        JOIN a.question q
                                        JOIN q.category c
                                   WHERE a.author = :author
                                         AND a.season = :season
                                         AND c.type = \'studijnyProgram\'
                                         AND (a.option IS NOT NULL OR a.comment IS NOT NULL)
                                   GROUP BY sp.id');
        $query->setParameter('author', $user);
        $query->setParameter('season', $season);
        $rows = $query->getResult();


        $result = array();
        $studyRepository = $em->getRepository('AnketaBundle\Entity\StudyProgram');
        foreach ($studyRepository->getStudyProgrammesForUser($user, $season) as $studyProgram) {
                $result[$studyProgram->getCode()] = array(
                    'answered' => 0,
                    'total' => $this->getNumberOfQuestionsForCategoryType(CategoryType::STUDY_PROGRAMME, $season)
                );
        }

        foreach ($rows as $row) {
            $result[$row['sp_code']]['answered'] = $row['num'];
        }

        return $result;
    }
}
