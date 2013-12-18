<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Controller
 * @author     Jakub Markoš <jakub.markos@gmail.com>
 */

/**
 * Controller for answering questions
 */

namespace AnketaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AnketaBundle\Entity\Answer;
use AnketaBundle\Entity\User;
use AnketaBundle\Entity\CategoryType;

class QuestionController extends AbstractVotingController {

    /**
     * Processes the form.
     *
     * @param Request $request
     * @param User $user current user
     * @param ArrayCollection $questions questions which are expected in the form
     * @param ArrayCollection $answers answers already filled before
     * @param array $answerColumns method names and values to set on the answer
     */
    private function processForm($request, $user, $questions, $answers, $season, $answerColumns) {
        $em = $this->get('doctrine.orm.entity_manager');

        $questionArray = $request->request->get('question');
        $savedSomething = false;

        $result = array();
        // prechadzame otazky na ktore sa ocakava mozna odpoved
        foreach ($questions as $question) {
            $id = $question->getId();
            if (!array_key_exists($id, $questionArray)) {
                // TODO(ppershing): throw an exception here?
                continue;
            }

            if (!empty($answers[$id])) {
                $answer = $answers[$id];
            } else {
                $answer = new Answer();

                $answer->setQuestion($question);
                $answer->setAuthor($user);
                $answer->setSeason($season);
            }

            foreach ($answerColumns as $method => $value) {
                $answer->$method($value);
            }

            if (isset($questionArray[$id]['answer'])) {
                $optionId = $questionArray[$id]['answer'];
                if ($optionId == -1) {
                    $option = null;
                } else {
                    $option = $em->find('AnketaBundle:Option', $optionId);
                    if (!($question->getOptions()->contains($option))) {
                        // TODO(ppershing): throw an exception
                        continue;
                    }
                }

                $answer->setOption($option);
            } else {
                $answer->setOption(null);
            }


            if (isset($questionArray[$id]['comment']) &&
                trim($questionArray[$id]['comment']) != '') {
                $answer->setComment(trim($questionArray[$id]['comment']));
            } else {
                $answer->setComment(null);
            }


            if ($answer->getOption() === null && $answer->getComment() === null) {
                $em->remove($answer);
            } else {
                $em->persist($answer);
                $savedSomething = true;
            }
        }

        if ($savedSomething) {
            $userSeason = $em->getRepository('AnketaBundle:UserSeason')->findOneBy(array('user' => $user->getId(), 'season' => $season->getId()));
            $userSeason->setParticipated(true);
            if ($userSeason->getStartTimestamp() == null) {
                $userSeason->setStartTimestamp(new \DateTime());
            }
        }
    }

    private function redirectAfterProcessing($activeItems = array()) {
        $request = $this->get('request');
        if ($request->request->get('next')) {
            $locale = $this->getRequest()->getLocale();
            return new RedirectResponse($this->get('anketa.menu.hlasovanie')->getNextSection($activeItems, $locale) ?: $request->getRequestUri());
        }
        else {
            return new RedirectResponse($request->getRequestUri());
        }
    }

    /**
     * Note: slug may be "-1" meaning default first subject
     */
    public function getAttendedSubjectBySlug($user, $slug) {
        $em = $this->get('doctrine.orm.entity_manager');
        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();
        $attendedSubjects = $em->getRepository('AnketaBundle\Entity\Subject')
                               ->getAttendedSubjectsForUser($user, $season);

        if (count($attendedSubjects) == 0) {
            return false;
        }

        // defaultne vraciame abecedne prvy predmet
        if ($slug == -1) {
            $subject = $attendedSubjects[0];
        } else {
            $subject = $em->getRepository('AnketaBundle\Entity\Subject')
                          ->findOneBy(array('slug' => $slug));
            if (empty($subject)) {
                $msg = $this->get('translator')->trans('question.controller.chybny_slug_predmetu', array('%slug%' => $slug));
                throw new \RuntimeException($msg);
            }
            if (!in_array($subject, $attendedSubjects)) {
                $msg = $this->get('translator')->trans('question.controller.nezapisany_predmet', array('%slug%' => $slug));
                throw new \RuntimeException($msg);
            }
        }

        return $subject;
    }

    /**
     * Note: slug may be "-1" meaning default first subject
     */
    public function getAttendedStudyProgrammeBySlug($user, $slug) {
        $em = $this->get('doctrine.orm.entity_manager');
        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();
        $attendedStudyProgrammes = $em->getRepository('AnketaBundle\Entity\StudyProgram')
                                      ->getStudyProgrammesForUser($user, $season);

        if (count($attendedStudyProgrammes) == 0) {
            $msg = $this->get('translator')->trans('question.controller.ziadne_programy');
            throw new \RuntimeException($msg);
        }

        // defaultne vraciame abecedne prvy predmet
        if ($slug == -1) {
            $studyProgramme = $attendedStudyProgrammes[0];
        } else {
            $studyProgramme = null;
            foreach ($attendedStudyProgrammes as $asp) if ($asp->getSlug() == $slug) {
                $studyProgramme = $asp;
                break;
            }
            if (empty($studyProgramme)) {
                $msg = $this->get('translator')->trans('question.controller.program_neexistuje', array('%slug%' => $slug));
                throw new \RuntimeException($msg);
            }
        }

        return $studyProgramme;
    }

    public function answerSubjectTeacherAction($subject_slug, $teacher_code) {
        $request = $this->get('request');
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->get('doctrine.orm.entity_manager');
        try {
            $subject = $this->getAttendedSubjectBySlug($user, $subject_slug);
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();
        $questions = $em->getRepository('AnketaBundle\Entity\Question')
                        ->getOrderedQuestionsByCategoryType(CategoryType::TEACHER_SUBJECT, $season);

        $teacherSubject = $em->getRepository('AnketaBundle:TeachersSubjects')
                             ->findOneBy(array('subject' => $subject->getId(),
                                               'season' => $season->getId(),
                                               'teacher' => $teacher_code));
        if (!$teacherSubject) {
            $msg = $this->get('translator')->trans('question.controller.ucitel_neuci', array('%teacher_code%' => $teacher_code));
            throw new NotFoundHttpException($msg);
        }
        $teacher = $teacherSubject->getTeacher();
        $studyProgram = $em->getRepository('AnketaBundle:StudyProgram')
                           ->getStudyProgrammeForUserSubject($user, $subject, $season);

        $studyYear = $em->getRepository('AnketaBundle:StudyProgram')
                           ->getStudyYearForUserSubject($user, $subject, $season);

        $answers = $em->getRepository('AnketaBundle\Entity\Answer')
                      ->getAnswersByCriteria($questions, $user, $season, $subject, $teacher);

        if ('POST' == $request->getMethod()) {
            $this->processForm($request, $user, $questions, $answers, $season, array(
                'setSubject' => $subject,
                'setTeacher' => $teacher,
                'setStudyProgram' => $studyProgram,
                'setStudyYear' => $studyYear,
                'setAttended' => true,
            ));

            $em->flush();

            return $this->redirectAfterProcessing(array('subject', $subject->getId(), $teacher->getId()));
        }

        $templateParams = array();
        $templateParams['title'] = $subject->getName() . ' - ' . $teacher->getFormattedName();
        $templateParams['activeItems'] = array('subject', $subject->getId(), $teacher->getId());
        $templateParams['questions'] = $questions;
        $templateParams['answers'] = $answers;
        $templateParams['categoryType'] = 'teacher_subject';
        $templateParams['subject'] = $subject;
        return $this->render('AnketaBundle:Question:index.html.twig', $templateParams);

    }

    public function answerSubjectAction($subject_slug) {
        $request = $this->get('request');
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->get('doctrine.orm.entity_manager');
        try {
            $subject = $this->getAttendedSubjectBySlug($user, $subject_slug);
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
        if ($subject === false) return new RedirectResponse($this->generateUrl('answer_general'));

        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();
        $questions = $em->getRepository('AnketaBundle\Entity\Question')
                        ->getOrderedQuestionsByCategoryType(CategoryType::SUBJECT, $season);
        $answers = $em->getRepository('AnketaBundle\Entity\Answer')
                      ->getAnswersByCriteria($questions, $user, $season, $subject);
        $studyProgram = $em->getRepository('AnketaBundle:StudyProgram')
                           ->getStudyProgrammeForUserSubject($user, $subject, $season);
        $studyYear = $em->getRepository('AnketaBundle:StudyProgram')
                           ->getStudyYearForUserSubject($user, $subject, $season);

        if ('POST' == $request->getMethod()) {
            $this->processForm($request, $user, $questions, $answers, $season, array(
                'setSubject' => $subject,
                'setTeacher' => null,
                'setStudyProgram' => $studyProgram,
                'setStudyYear' => $studyYear,
                'setAttended' => true,
            ));

            $em->flush();

            return $this->redirectAfterProcessing(array('subject', $subject->getId()));
        }

        $templateParams = array();
        $templateParams['title'] = $subject->getName();
        $templateParams['activeItems'] = array('subject', $subject->getId());
        $templateParams['questions'] = $questions;
        $templateParams['answers'] = $answers;
        $templateParams['categoryType'] = 'subject';
        $templateParams['subject'] = $subject;
        return $this->render('AnketaBundle:Question:index.html.twig', $templateParams);
    }

    public function answerStudyProgramAction($slug) {

        $request = $this->get('request');
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->get('doctrine.orm.entity_manager');
        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();
        try {
            $studyProgramme = $this->getAttendedStudyProgrammeBySlug($user, $slug);
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();
        $studyYear = $em->getRepository('AnketaBundle:StudyProgram')
                        ->getStudyYearForUser($user, $season, $studyProgramme);
        $questions = $em->getRepository('AnketaBundle\Entity\Question')
                        ->getOrderedQuestionsByCategoryType(CategoryType::STUDY_PROGRAMME, $season);
        $answers = $em->getRepository('AnketaBundle\Entity\Answer')
                      ->getAnswersByCriteria($questions, $user, $season, null, null, $studyProgramme);

        if ('POST' == $request->getMethod()) {
            $this->processForm($request, $user, $questions, $answers, $season, array(
                'setStudyProgram' => $studyProgramme,
                'setSubject' => null,
                'setTeacher' => null,
                'setStudyYear' => $studyYear,
                // aktualne sa daju vyplnat iba predmety ktore sme navstevovali
                'setAttended' => true,
            ));

            $em->flush();

            return $this->redirectAfterProcessing(array('study_program', $studyProgramme->getCode()));
        }

        $templateParams = array();
        $templateParams['title'] = $studyProgramme->getName().' ('.$studyProgramme->getCode().')';
        $templateParams['activeItems'] = array('study_program', $studyProgramme->getCode());
        $templateParams['questions'] = $questions;
        $templateParams['answers'] = $answers;
        $templateParams['categoryType'] = 'study_program';
        $templateParams['subject'] = null;
        return $this->render('AnketaBundle:Question:index.html.twig', $templateParams);
    }

    public function answerGeneralAction($id) {
        /**
         * Co sa tu robi?
         *   - spracovanie parametru
         *   - ziskat potrebne otazky
         *   - ziskat odpovede, ak existuju, a poslat ich tiez do templatu
         * Ak je POST request, tak treba naviac
         *   - spracovanie formovych dat (fcia processForm)
         *   - updatovanie / vytvorenie odpovedi (fcia processForm)
         *   - persistovanie odpovedi
         */
        $request = $this->get('request');
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->get('doctrine.orm.entity_manager');
        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();

        // chceme vceobecne subkategorie - pre menu do templatu
        // TODO toto je code duplication s buildMenu, tuto informaciu aj tak dostaneme v templateParams
        $subcategories = $em->getRepository('AnketaBundle\Entity\Category')
                       ->getOrderedGeneral($season);
        if (empty($subcategories)) {
            $msg = $this->get('translator')->trans('question.controller.ziadne_podkategorie');
            throw new NotFoundHttpException($msg);
        }

        // default prva kategoria (najnizsie position)
        if ($id == -1) {
            $category = $subcategories[0];
        } else {
            // kontrola na integer sa odohrala uz v routovani
            $category = $em->find('AnketaBundle:Category', $id);

            if (empty($category) ||
                ($category->getType() !== CategoryType::GENERAL)) {
                $msg = $this->get('translator')->trans('question.controller.chybna_kategoria', array('%id%' => $id));
                throw new NotFoundHttpException($msg);
            }
        }

        $questions = $em->getRepository('AnketaBundle\Entity\Question')
                        ->getOrderedQuestions($category, $season);
        $answers = $em->getRepository('AnketaBundle\Entity\Answer')
                      ->getAnswersByCriteria($questions, $user, $season);

        if ('POST' == $request->getMethod()) {
            // k odpovediam na vseobecne otazky dame prvy studijny program, co user ma
            $studyProgram = $em->getRepository('AnketaBundle\Entity\StudyProgram')->
                getFirstStudyProgrammeForUser($user, $season);
            $studyYear = $em->getRepository('AnketaBundle:StudyProgram')
                            ->getStudyYearForUser($user, $season, $studyProgram);

            $this->processForm($request, $user, $questions, $answers, $season, array(
                'setStudyProgram' => $studyProgram,
                'setStudyYear' => $studyYear,
            ));

            $em->flush();

            return $this->redirectAfterProcessing(array('general', $category->getId()));
        }

        $templateParams = array();
        $templateParams['title'] = $category->getDescription($this->getRequest()->getLocale());
        $templateParams['activeItems'] = array('general', $category->getId());
        $templateParams['questions'] = $questions;
        $templateParams['answers'] = $answers;
        $templateParams['categoryType'] = 'general';
        $templateParams['subject'] = null;
        return $this->render('AnketaBundle:Question:index.html.twig', $templateParams);
    }

    public function answerIncompleteAction() {
        //TODO(majak): spravit redirect na prvu sekciu,
        //             ktora nema 100% vyplnenie
        return new RedirectResponse($this->generateUrl('answer'));
    }
}
