<?php
/**
 * @copyright Copyright (c) 2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Menu
 */

namespace AnketaBundle\Menu;

use Symfony\Component\DependencyInjection\ContainerInterface;

class HlasovanieMenu
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    private function generateUrl($route, $parameters = array(), $absolute = false) {
        return $this->container->get('router')->generate($route, $parameters, $absolute);
    }

    /**
     *
     * @return array menu array of categories and their subcategories
     */
    private function buildMenu() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $em = $this->container->get('doctrine.orm.entity_manager');
        $trans = $this->container->get('translator');

        $season = $em->getRepository('AnketaBundle:Season')->getActiveSeason();

        $menu = array(
            'subject' => new MenuItem(
                $trans->trans('hlasovanie.menu.predmety'),
                $this->generateUrl('answer_subject')
            ),
            'study_program' => new MenuItem(
                $trans->trans('hlasovanie.menu.studijne_programy'),
                $this->generateUrl('answer_study_program')
            ),
            'general' => new MenuItem(
                $trans->trans('hlasovanie.menu.vseobecne'),
                $this->generateUrl('answer_general')
            )
        );

        // pridame menu pre vseobecne otazky
        $subcategories = $em->getRepository('AnketaBundle\Entity\Category')
                       ->getOrderedGeneral($season);
        foreach ($subcategories as $subcategory) {
            $menu['general']->children[$subcategory->getId()] =
                new MenuItem(
                    $subcategory->getDescription($this->container->get('request')->getLocale()),
                    $this->generateUrl('answer_general', array('id' => $subcategory->getId()))
                    );
        }
        if (empty($subcategories)) unset($menu['general']);

        // pridame menu pre predmety
        $subjects = $em->getRepository('AnketaBundle\Entity\Subject')
                       ->getAttendedSubjectsForUser($user, $season);
        $teacherSubjectRepository = $em->getRepository('AnketaBundle:TeachersSubjects');
        foreach($subjects as $subject) {
            $subjectMenu =
                new MenuItem(
                $subject->getName(),
                $this->generateUrl('answer_subject', array('subject_slug' => $subject->getSlug()))
                );
            // TODO: optimalizovat selecty
            // pridame vnorene menu pre predmetoucitelov
            $teachersSubjects = $teacherSubjectRepository->findBy(array('subject' => $subject->getId(), 'season' => $season->getId()));
            foreach ($teachersSubjects as $teacherSubject) {
                $teacher = $teacherSubject->getTeacher();
                $teacherItem = new MenuItem(
                            $teacher->getFormattedName(),
                            $this->generateUrl('answer_subject_teacher',
                                array('subject_slug' => $subject->getSlug(),
                                      'teacher_code' => $teacher->getId()))
                            );
                if ($teacherSubject->getLecturer()) $teacherItem->lecturer = true;
                if ($teacherSubject->getTrainer()) $teacherItem->trainer = true;
                $subjectMenu->children[$teacher->getId()] = $teacherItem;
            }
            $menu['subject']->children[$subject->getId()] = $subjectMenu;
        }
        if (empty($subjects)) unset($menu['subject']);

        // pridame menu pre studijne programy
        $studyProgrammes = $em->getRepository('AnketaBundle\Entity\StudyProgram')
                       ->getStudyProgrammesForUser($user, $season);
        foreach ($studyProgrammes as $studyProgramme) {
            $menu['study_program']->children[$studyProgramme->getCode()] =
                new MenuItem(
                    $studyProgramme->getName().' ('.$studyProgramme->getCode().')',
                    $this->generateUrl('answer_study_program', array('slug' => $studyProgramme->getSlug()))
                    );
        }
        if (empty($studyProgrammes)) unset($menu['study_program']);


        // nastavime progress
        $questionRepository = $em->getRepository('AnketaBundle\Entity\Question');

        foreach ($questionRepository->getProgressForSubjectTeachersByUser($user, $season) as $subject => $rest) {
            foreach ($rest as $teacher => $progress) {

                $menu['subject']->children[$subject]
                                ->children[$teacher]
                                ->getProgressbar()
                                ->setProgress((int)$progress['answered'],
                                              (int)$progress['total']);
            }
        }

        foreach ($questionRepository->getProgressForSubjectsByUser($user, $season) as $subject => $progress) {
            $menu['subject']->children[$subject]
                            ->getProgressbar()
                            ->setProgress((int)$progress['answered'],
                                          (int)$progress['total']);
            $menu['subject']->children[$subject]
                            ->getProgressbar()
                            ->setIncludeChildren(false);
        }

        foreach ($questionRepository->getProgressForCategoriesByUser($user, $season) as $categoryId => $progress) {
            if (array_key_exists($categoryId, $menu['general']->children)) {
                $menu['general']->children[$categoryId]
                                ->getProgressbar()
                                ->setProgress((int)$progress['answered'],
                                              (int)$progress['total']);
            }
        }

        foreach ($questionRepository->getProgressForStudyProgramsByUser($user, $season) as $studyProgramId => $progress) {
            if (array_key_exists($studyProgramId, $menu['study_program']->children)) {
                $menu['study_program']->children[$studyProgramId]
                                ->getProgressbar()
                                ->setProgress((int)$progress['answered'],
                                              (int)$progress['total']);
            }
        }

        # remove menu items containing zero questions
        $menu = $this->sanitizeMenu($menu);

        # pridaj do menu link na ukoncenie hlasovania
        $menu['anonymizuj'] = new MenuItem(
            $trans->trans('hlasovanie.menu.ukoncenie'),
            $this->generateUrl('anonymizuj')
        );

        return $menu;
    }

    /**
      * Traverse menu tree and remove subtrees with zero questions
      */
    private function sanitizeMenu($menu) {
         foreach ($menu as $key => $value) {
             $pb = $value->getProgressbar();
             $newmenu = $this->sanitizeMenu($value->children);

             if ($pb->getProgressTotal() == 0) {
                 unset($menu[$key]);
             } else {
                 $menu[$key]->children = $newmenu;
             }
         }
         return $menu;
    }

    public function render($activeItems = array()) {
        $templateParams = array('menu' => $this->buildMenu());

        $activeTail = null;
        $current = &$templateParams['menu'];
        foreach ($activeItems as $item) {
            $activeTail = $current[$item];
            $current[$item]->expanded = true;
            $current = &$current[$item]->children;
        }
        if ($activeTail) {
            $activeTail->active = true;
        }

        return $this->container->get('templating')->render('AnketaBundle::menu.html.twig',
                                                           $templateParams);
    }

    public function getNextSection($activeItems = array()) {
        $menu = $this->buildMenu();

        // pre kazdu aktivnu polozku spocitame jej praveho surodenca.
        $nextSibling = array();
        $current = &$menu;
        foreach ($activeItems as $item) {
            $siblings = array_keys($current);
            $myIndex = array_search($item, $siblings);
            $nextSibling[] = ($myIndex === false || $myIndex + 1 == count($siblings) ? null : $current[$siblings[$myIndex + 1]]);

            $current = &$current[$item]->children;
        }

        $myChildren = array_keys($current);
        if (!empty($myChildren)) {
            return $current[$myChildren[0]]->href;
        }

        for ($i = count($nextSibling) - 1; $i >= 0; $i--) {
            if($nextSibling[$i] !== null) {
                return $nextSibling[$i]->href;
            }
        }

        return null;
    }

}
