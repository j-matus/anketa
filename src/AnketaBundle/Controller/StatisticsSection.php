<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Controller
 */

namespace AnketaBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use AnketaBundle\Entity\Season;
use AnketaBundle\Entity\Subject;
use AnketaBundle\Entity\User;
use AnketaBundle\Entity\Question;
use AnketaBundle\Entity\StudyProgram;
use AnketaBundle\Entity\Answer;
use AnketaBundle\Entity\Response;
use AnketaBundle\Entity\CategoryType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StatisticsSection extends ContainerAware {

    ///// I. the interesting part: various constructors

    protected function __construct() {
    }

    public static function makeSubjectTeacherSection(ContainerInterface $container, Season $season, Subject $subject, User $teacher) {
        $em = $container->get('doctrine.orm.entity_manager');
        if ($em->getRepository('AnketaBundle:TeachersSubjects')->findOneBy(array('teacher' => $teacher->getId(), 'subject' => $subject->getId(), 'season' => $season->getId())) === null) {
            throw new NotFoundHttpException('Section not found: Teacher "'.$teacher->getId().'" doesn\'t teach subject "'.$subject->getId().'".');
        }
        $result = new StatisticsSection();
        $result->setContainer($container);
        $result->season = $season;
        $result->subject = $subject;
        $result->teacher = $teacher;
        $result->title = $subject->getCode() . ' ' . $subject->getName() . ' - ' . $teacher->getFormattedName();
        $result->questionsCategoryType = CategoryType::TEACHER_SUBJECT;
        $result->answersQuery = array('subject' => $subject->getId(), 'teacher' => $teacher->getId());
        $result->responsesQuery = array('season' => $season->getId(), 'subject' => $subject->getId(), 'teacher' => $teacher->getId(), 'studyProgram' => null);
        $result->activeMenuItems = array($season->getId(), 'subjects', $subject->getCategory(), $subject->getId(), $teacher->getId());
        $result->slug = $season->getSlug() . '/predmet/' . $subject->getSlug() . '/ucitel/' . $teacher->getId();
        $result->associationExamples = 'prednášajúci, cvičiaci, garant predmetu';
        return $result;
    }

    public static function makeSubjectSection(ContainerInterface $container, Season $season, Subject $subject) {
        $em = $container->get('doctrine.orm.entity_manager');
        $result = new StatisticsSection();
        $result->setContainer($container);
        $result->season = $season;
        $result->subject = $subject;
        $result->title = $subject->getCode() . ' ' . $subject->getName();
        $result->questionsCategoryType = CategoryType::SUBJECT;
        $result->answersQuery = array('subject' => $subject->getId());
        $result->responsesQuery = array('season' => $season->getId(), 'subject' => $subject->getId(), 'teacher' => null, 'studyProgram' => null);
        $result->activeMenuItems = array($season->getId(), 'subjects', $subject->getCategory(), $subject->getId());
        $result->slug = $season->getSlug() . '/predmet/' . $subject->getSlug();
        $result->associationExamples = 'prednášajúci, cvičiaci, garant predmetu';
        return $result;
    }

    public static function makeGeneralSection(ContainerInterface $container, Season $season, Question $generalQuestion) {
        if ($generalQuestion->getCategory()->getType() != CategoryType::GENERAL) {
            throw new NotFoundHttpException('Section not found: Question is not general.');
        }
        $result = new StatisticsSection();
        $result->setContainer($container);
        $result->season = $season;
        $result->generalQuestion = $generalQuestion;
        $result->title = $generalQuestion->getQuestion();
        $result->questionsCategoryType = CategoryType::GENERAL;
        $result->headingVisible = false;
        $result->answersQuery = array();
        $result->responsesQuery = array('season' => $season->getId(), 'question' => $generalQuestion->getId());
        $result->activeMenuItems = array($season->getId(), 'general');
        $result->slug = $season->getSlug() . '/vseobecne/' . $generalQuestion->getId();
        $result->associationExamples = 'vedenie fakulty, vedúci katedry, vyučujúci';
        return $result;
    }

    public static function makeStudyProgramSection(ContainerInterface $container, Season $season, StudyProgram $studyProgram) {
        $result = new StatisticsSection();
        $result->setContainer($container);
        $result->season = $season;
        $result->studyProgram = $studyProgram;
        $result->title = $studyProgram->getCode() . ' ' . $studyProgram->getName();
        $result->questionsCategoryType = CategoryType::STUDY_PROGRAMME;
        $result->answersQuery = array('studyProgram' => $studyProgram->getId());
        $result->responsesQuery = array('season' => $season->getId(), 'studyProgram' => $studyProgram->getId(), 'teacher' => null, 'subject' => null);
        $result->activeMenuItems = array($season->getId(), 'study_programs', $studyProgram->getCode());
        $result->slug = $season->getSlug() . '/program/' . $studyProgram->getSlug();
        $result->associationExamples = 'garant, tútor, vedúci katedry, vyučujúci niektorého predmetu';
        return $result;
    }

    public static function getSectionOfAnswer(ContainerInterface $container, Answer $answer) {
        $category = $answer->getQuestion()->getCategory()->getType();
        if ($category == CategoryType::TEACHER_SUBJECT) return self::makeSubjectTeacherSection($container, $answer->getSeason(), $answer->getSubject(), $answer->getTeacher());
        if ($category == CategoryType::SUBJECT) return self::makeSubjectSection($container, $answer->getSeason(), $answer->getSubject());
        if ($category == CategoryType::GENERAL) return self::makeGeneralSection($container, $answer->getSeason(), $answer->getQuestion());
        if ($category == CategoryType::STUDY_PROGRAMME) return self::makeStudyProgramSection($container, $answer->getSeason(), $answer->getStudyProgram());
        throw new \Exception('Unknown category type');
    }

    public static function getSectionOfResponse(ContainerInterface $container, Response $response) {
        if ($response->getTeacher() !== null) return self::makeSubjectTeacherSection($container, $response->getSeason(), $response->getSubject(), $response->getTeacher());
        if ($response->getSubject() !== null) return self::makeSubjectSection($container, $response->getSeason(), $response->getSubject());
        if ($response->getQuestion() !== null) return self::makeGeneralSection($container, $response->getSeason(), $response->getQuestion());
        if ($response->getStudyProgram() !== null) return self::makeStudyProgramSection($container, $response->getSeason(), $response->getStudyProgram());
        throw new \Exception('Unknown type of response');
    }

    public static function getSectionFromSlug(ContainerInterface $container, $slug) {
        $em = $container->get('doctrine.orm.entity_manager');
        if (!preg_match('@^([a-z0-9-]+)/(.*[^/])/*$@', $slug, $matches)) {
            throw new NotFoundHttpException('Section not found: Section slug doesn\'t start with season slug.');
        }
        $season = $em->getRepository('AnketaBundle:Season')->findOneBy(array('slug' => $matches[1]));
        if ($season === null) {
            throw new NotFoundHttpException('Section not found: Season "'.$matches[1].'" not found.');
        }
        $slug = $matches[2];
        if (preg_match('@^predmet/([a-zA-Z0-9-_]+)/ucitel/(\d+)$@', $slug, $matches)) {
            $subject = $em->getRepository('AnketaBundle:Subject')->findOneBy(array('slug' => $matches[1]));
            if ($subject === null) {
                throw new NotFoundHttpException('Section not found: Subject "'.$matches[1].'" not found.');
            }
            $teacher = $em->find('AnketaBundle:User', $matches[2]);
            if ($teacher === null) {
                throw new NotFoundHttpException('Section not found: Teacher "'.$matches[2].'" not found.');
            }
            return self::makeSubjectTeacherSection($container, $season, $subject, $teacher);
        }
        if (preg_match('@^predmet/([a-zA-Z0-9-_]+)$@', $slug, $matches)) {
            $subject = $em->getRepository('AnketaBundle:Subject')->findOneBy(array('slug' => $matches[1]));
            if ($subject === null) {
                throw new NotFoundHttpException('Section not found: Subject "'.$matches[1].'" not found.');
            }
            return self::makeSubjectSection($container, $season, $subject);
        }
        if (preg_match('@^vseobecne/(\d+)$@', $slug, $matches)) {
            $question = $em->find('AnketaBundle:Question', $matches[1]);
            if ($question === null) {
                throw new NotFoundHttpException('Section not found: Question "'.$matches[1].'" not found.');
            }
            return self::makeGeneralSection($container, $season, $question);
        }
        if (preg_match('@^program/([a-zA-Z0-9-_]+)$@', $slug, $matches)) {
            $program = $em->getRepository('AnketaBundle:StudyProgram')->findOneBy(array('slug' => $matches[1]));
            if ($program === null) {
                throw new NotFoundHttpException('Section not found: Program "'.$matches[1].'" not found.');
            }
            return self::makeStudyProgramSection($container, $season, $program);
        }
        throw new NotFoundHttpException('Section not found: Bad section slug format.');
    }

    ///// II. the boring part: instance variables and their accessors

    private $season = null;

    public function getSeason() {
        return $this->season;
    }

    private $subject = null;

    public function getSubject() {
        return $this->subject;
    }

    private $teacher = null;

    public function getTeacher() {
        return $this->teacher;
    }

    private $generalQuestion = null;

    public function getGeneralQuestion() {
        return $this->generalQuestion;
    }

    private $studyProgram = null;

    public function getStudyProgram() {
        return $this->studyProgram;
    }

    private $title = null;

    public function getTitle() {
        return $this->title;
    }

    private $headingVisible = true;

    public function getHeadingVisible() {
        return $this->headingVisible;
    }

    private $preface = null;

    public function getPreface() {
        if ($this->preface === null) {
            if ($this->getSubject() && $this->getSeason()) {
                $preface = '';
                $em = $this->container->get('doctrine.orm.entity_manager');
                $subject = $this->getSubject();
                $teacher = $this->getTeacher();
                $season = $this->getSeason();
                $skratka_fakulty = $this->container->getParameter('skratka_fakulty');
                $totalStudents = 0;
                $subjectSeason = $em->getRepository('AnketaBundle:SubjectSeason')->findOneBy(array(
                    'subject' => $subject,
                    'season' => $season
                ));
                if (isset($subjectSeason)) {
                    if ($subjectSeason->getStudentCountFaculty() !== null) {
                        $scf = $subjectSeason->getStudentCountFaculty();
                        $preface .= 'Tento predmet ';
                        if ($scf == 0) $preface .= 'nemal nikto z '.$skratka_fakulty.' zapísaný';
                        if ($scf == 1) $preface .= 'mal zapísaný '.$scf.' študent '.$skratka_fakulty;
                        if ($scf >= 2 && $scf <= 4) $preface .= 'mali zapísaní '.$scf.' študenti '.$skratka_fakulty;
                        if ($scf >= 5) $preface .= 'malo zapísaných '.$scf.' študentov '.$skratka_fakulty;
                        if ($subjectSeason->getStudentCountAll() !== null) {
                            $totalStudents = $subjectSeason->getStudentCountAll();
                            $sco = $totalStudents - $scf;
                            if ($sco) $preface .= ' ('.$sco.' z iných fakúlt)';
                        }
                        $preface .= '.';
                    }
                    else if ($subjectSeason->getStudentCountAll() !== null) {
                        $sca = $subjectSeason->getStudentCountAll();
                        $preface .= 'Tento predmet ';
                        if ($sca == 0) $preface .= 'nemal nikto zapísaný';
                        if ($sca == 1) $preface .= 'mal zapísaný '.$sca.' študent';
                        if ($sca >= 2 && $sca <= 4) $preface .= 'mali zapísaní '.$sca.' študenti';
                        if ($sca >= 5) $preface .= 'malo zapísaných '.$sca.' študentov';
                        $preface .= '.';
                        $totalStudents = $sca;
                    }

                }

                $studentov = function ($count) {
                    if ($count == 0) return 'sa nevyjadril žiaden študent';
                    if ($count == 1) return 'sa vyjadril jeden študent';
                    if ($count < 4) return 'sa vyjadrili '.$count.' študenti';
                    if ($count >= 4) return 'sa vyjadrilo '.$count.' študentov';
                };

                $votingSummary = $em->getRepository('AnketaBundle:SectionVoteSummary')->findOneBy(array(
                    'subject' => $subject,
                    'season' => $season,
                    'teacher' => $teacher
                ));
                if ($votingSummary) {
                    $voters = $votingSummary->getCount();
                    if ($teacher) {
                        $preface .= ' K tomuto vyučujúcemu ';
                    } else {
                        $preface .= ' K predmetu ';
                    }
                    $preface .= $studentov($voters);
                    if ($totalStudents) {
                        $preface .= ' ('.round($voters/$totalStudents * 100, 2). '% z '.$totalStudents.').';
                    } else {
                        $preface .= '.';
                    }
                }
                $this->preface = $preface;
            } else {
                $this->preface = '';
            }
        }
        return $this->preface;
    }

    private $minVoters = 0;

    public function getMinVoters() {
        return $this->minVoters;
    }

    private $questionsCategoryType = null;

    public function getQuestions() {
        if ($this->generalQuestion) return array($this->generalQuestion);
        $em = $this->container->get('doctrine.orm.entity_manager');
        return $em->getRepository('AnketaBundle:Question')->getOrderedQuestionsByCategoryType($this->questionsCategoryType, $this->season);
    }

    private $answersQuery = null;

    public function getAnswers($question) {
        $query = array_merge($this->answersQuery, array('question' => $question->getId()));
        $em = $this->container->get('doctrine.orm.entity_manager');
        return $em->getRepository('AnketaBundle:Answer')->findBy($query);
    }

    // TODO public function getQuestionsAndAnswers() or something like that

    private $responsesQuery = null;

    public function getResponses() {
        $em = $this->container->get('doctrine.orm.entity_manager');
        return $em->getRepository('AnketaBundle:Response')->findBy($this->responsesQuery);
    }

    private $activeMenuItems = null;

    public function getActiveMenuItems() {
        return $this->activeMenuItems;
    }

    private $slug = null;

    /**
     * Get slug for the section.
     * 
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Get path for the section based on the slug.
     * 
     * @param bool $absolute
     * @param string $slug
     * @return string
     */
    public function getStatisticsPath($absolute = false) {
        return $this->container->get('router')->generate('statistics_results', array('section_slug' => $this->getSlug()), $absolute);
    }
    
    private $previousSection = null;
    
    /**
     * Gets the previous section object.
     */
    public function getPreviousSection() {
        if ($this->previousSection !== null) return $this->previousSection;

        $em = $this->container->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->select('a')
        ->from('AnketaBundle:Answer', 'a')->from('AnketaBundle:Question', 'q')
        ->from('AnketaBundle:Category', 'c')->from('AnketaBundle:Season', 'sn')
        ->where('a.question = q')
        ->andWhere('q.category = c')
        ->andWhere($qb->expr()->eq('c.type', '?1'))
        ->andWhere('a.season = sn')
        ->andWhere($qb->expr()->lt('sn.ordering', '?2'))
        ->orderBy('sn.ordering', 'DESC');
        foreach (array('teacher', 'subject', 'studyProgram') as $col) {
            if (!empty($this->answersQuery[$col])) {
                $qb->andWhere($qb->expr()->eq("a.$col", $this->answersQuery[$col]));
            }
        }

        $qb->setParameters(array(1 => $this->questionsCategoryType, 2 => $this->season->getOrdering()));
        $qb->setMaxResults(1);
        $answer = $qb->getQuery()->getOneOrNullResult();

        if ($answer == null) return null;

        $this->previousSection = self::getSectionOfAnswer($this->container, $answer);
        return $this->previousSection;
    }
    
    private $associationExamples = null;

    public function getAssociationExamples() {
        return $this->associationExamples;
    }

    public function getTeacherOptedOut() {
        if ($this->getTeacher()) {
            return $this->getTeacher()->getHideAllResults();
        }
        if ($this->getSubject()) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $foundTrue = false;
            $foundFalse = false;
            $teachersSubjects = $em->getRepository('AnketaBundle:TeachersSubjects')->findBy(
                array('subject' => $this->getSubject(), 'season' => $this->getSeason()));
            foreach ($teachersSubjects as $ts) {
                $teacher = $ts->getTeacher();
                if ($teacher->getHideAllResults()) $foundTrue = true;
                else $foundFalse = true;
            }
            if ($this->getSeason()->getSubjectHiding() == Season::HIDE_SUBJECT_IF_ANY) {
                return $foundTrue;
            }
            if ($this->getSeason()->getSubjectHiding() == Season::HIDE_SUBJECT_IF_ALL) {
                return $foundTrue && !$foundFalse;
            }
        }
        return false;
    }

}

