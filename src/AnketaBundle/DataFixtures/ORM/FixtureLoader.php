<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Controller__DataFixtures__ORM
 * @author     Jakub Markoš <jakub.markos@gmail.com>
 */

namespace AnketaBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use AnketaBundle\Entity\Question;
use AnketaBundle\Entity\Option;
use AnketaBundle\Entity\Category;
use AnketaBundle\Entity\Answer;
use AnketaBundle\Entity\Subject;
use AnketaBundle\Entity\User;
use AnketaBundle\Entity\Role;
use AnketaBundle\Entity\Season;
use AnketaBundle\Entity\StudyProgram;
use AnketaBundle\Entity\UsersSubjects;
use AnketaBundle\Entity\TeachersSubjects;
use AnketaBundle\Entity\UserSeason;
use AnketaBundle\Entity\SubjectSeason;
use DateTime;

/**
 * Class for loading basic development data
 *
 * @package    Anketa
 * @subpackage Anketa__Controller__DataFixtures__ORM
 * @author     Jakub Markoš <jakub.markos@gmail.com>
 */

class FixtureLoader implements FixtureInterface {

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param object $manager
     */
    public function load(ObjectManager $manager) {
        // create categories
        /*$cat1 = new Category('general', 'Vyzor skoly');
        $cat2 = new Category('general', 'Moznosti stravovania');
        $cat3 = new Category('general', 'Ucitelia');
        $cat4 = new Category('subject', 'Predmety');

        $manager->persist($cat1);
        $manager->persist($cat2);
        $manager->persist($cat3);
        $manager->persist($cat4);

        // create questions and their options
        $question1 = new Question('Ako sa vam paci nasa skola?');
        $question1->setCategory($cat1);

        // 1. samotne options nemusime persistovat, lebo Question ma nastavene
        // cascade=persist (aj remove) pre relaciu s Option - tzn doctrine
        // sa o to postara
        // 2. Question->addOption sa zaroven stara o druhu stranu relacie -
        // tzn zavola sa Option->setQuestion
        $o1 = new Option('celkom pekna', 50);
        $o2 = new Option('nepaci sa mi', 0);
        $o3 = new Option('chcelo by to premalovat', 100);
        $question1->setOptions(new ArrayCollection(array('0' => $o1, $o2, $o3)));

        $question2 = new Question('Co nove ste sa dozvedeli na tomto predmete?');
        $question2->setCategory($cat4);
        $question2->setDescription('Berte do uvahy mnozstvo informacii a podobne.');
        $op1 = new Option('nic', 0);
        $op2 = new Option('velmi vela', 100);
        $question2->addOption($op1);
        $question2->addOption($op2);

        $question5 = new Question('Aku cast prednasok ste absolvovali?');
        $question5->setCategory($cat4);
        $opt1 = new Option('0-20%', 10);
        $opt2 = new Option('21-40%', 30);
        $opt3 = new Option('41-60%', 50);
        $opt4 = new Option('61-80%', 70);
        $opt5 = new Option('90-100%', 100);
        $question5->addOption($opt1);
        $question5->addOption($opt2);
        $question5->addOption($opt3);
        $question5->addOption($opt4);
        $question5->addOption($opt5);
        $question5->setHasComment(false);

        $question3 = new Question('Ohodnotte osvetlenie chodieb');
        $question3->setCategory($cat1);
        $question3->setStars(true);
        $question3->generateStarOptions();

        $question4 = new Question('Ohodnotte pristup ucitelov k ziakom');
        $question4->setCategory($cat3);
        $question4->setStars(true);
        $question4->generateStarOptions();

        $manager->persist($question1);
        $manager->persist($question2);
        $manager->persist($question3);
        $manager->persist($question4);
        $manager->persist($question5);
*/
        // Vytvorime defaultnu Season
        $season = new Season('Leto 2011/2012', '2011-2012-leto');
        $season->setActive(true);
        $season->setStudentCount(1800);
        $season->setVotingOpen(true);
        $season->setRespondingOpen(false);
        $season->setOrdering(0);
        $manager->persist($season);

        // create teacher + subject
        $teacher1 = new Teacher('Janko', 'Hraško', 'RNDr. Janko Hraško, PhD.', 'hrasko1');
        $teacher2 = new Teacher('Jožko', 'Mrkvička', 'Mgr. Jožko Mrkvička', 'mrkvicka47');
        $teacher3 = new Teacher('James', 'Burton', 'Mgr. Art. James Burton', 'burton42');


        $sub1 = new Subject('Metalyza');
        $sub1->setCode('met001');
        $sub1->setSlug('met001');
        $sub2 = new Subject('Agilné techniky v praxi');
        $sub2->setCode('agil056');
        $sub2->setSlug('agil056');
        $sub3 = new Subject('Telesná výchova');
        $sub3->setCode('tv06');
        $sub3->setSlug('tv06');
        // predmet ktory nikto nenavstevuje
        $sub4 = new Subject('FMFI voľno');
        $sub4->setCode('fmfi');

        // priradenie ucitelov k predmetom
        $manager->persist(new TeachersSubjects($teacher1, $sub1, $season, true));
        $manager->persist(new TeachersSubjects($teacher2, $sub2, $season));
        $manager->persist(new TeachersSubjects($teacher2, $sub3, $season, true));
        $manager->persist(new TeachersSubjects($teacher3, $sub3, $season, true, true));

        // neni nastavene cascadovanie, kedze neviem ktorym smerom sa to
        // bude castejsie generovat - ci sa budu vyrabat predmety a k nim
        // pridavat ucitelia, alebo naopak
        // => takze musime najprv pridat ucitela, potom predmet
        $manager->persist($teacher1);
        $manager->persist($teacher2);
        $manager->persist($teacher3);
        $manager->persist($sub1);
        $manager->persist($sub2);
        $manager->persist($sub3);
        $manager->persist($sub4);

        $subSeason1 = new SubjectSeason();
        $subSeason1->setSeason($season);
        $subSeason1->setSubject($sub1);
        $subSeason2 = new SubjectSeason();
        $subSeason2->setSeason($season);
        $subSeason1->setSubject($sub2);
        $subSeason3 = new SubjectSeason();
        $subSeason3->setSeason($season);
        $subSeason1->setSubject($sub3);
        $subSeason4 = new SubjectSeason();
        $subSeason4->setSeason($season);
        $subSeason1->setSubject($sub4);
        $manager->persist($subSeason1);
        $manager->persist($subSeason2);
        $manager->persist($subSeason3);
        $manager->persist($subSeason4);

        // create answers
//        $a = new Answer();
//        $a->setQuestion($question2);
//        $a->setComment('velmi podareny predmet');
//        $a->setOption($op2);
//        $a->setSubject($sub1);
//        $a->setTeacher($teacher1);
//
//        $a2 = new Answer();
//        $a2->setQuestion($question3);
//        $a2->setComment('vecer byva obcas malo svetla');
//        $a2->setOption($question3->getOptions()->get(2));
//
//        $manager->persist($a);
//        $manager->persist($a2);

        // create users, roles
        $userFoo = new User('foo');
        $userFoo->setDisplayName('Bc. Foo');
        $userAdmin = new User('admin', 'admin');
        $userAdmin->setDisplayName('admin');

        $roleAdmin = new Role('ROLE_ADMIN');
        $roleUser = new Role('ROLE_USER');
        $roleSuperAdmin = new Role('ROLE_SUPER_ADMIN');

        $studyProgramINF = new StudyProgram();
        $studyProgramINF->setCode('INF');
        $studyProgramINF->setName('informatika');
        $studyProgramINF->setSlug('INF');
        $manager->persist($studyProgramINF);

        $studyProgramMINF = new StudyProgram();
        $studyProgramMINF->setCode('mINF');
        $studyProgramMINF->setName('informatika');
        $studyProgramMINF->setSlug('mINF');
        $manager->persist($studyProgramMINF);

        $developers = array('sucha14'   =>  'Bc. Martin Sucha',
                            'trancik1'  =>  'Bc. Ivan Trančík',
                            'marek11'   =>  'Bc. Jakub Marek',
                            'belan14'   =>  'Tomáš Belan',
                            'kralik3'   =>  'Mgr. Martin Králik');

        $subs = array($sub1, $sub2, $sub3);

        foreach ($developers as $login => $displayName) {
            $user = new User($login);
            $user->setDisplayName($displayName);
            $user->addRole($roleSuperAdmin);

            foreach ($subs as $idx=>$sub) {
                $usersSubjects = new UsersSubjects();
                $usersSubjects->setSeason($season);
                $usersSubjects->setStudyProgram(($idx%2==0)?$studyProgramMINF:$studyProgramINF);
                $usersSubjects->setSubject($sub);
                $usersSubjects->setUser($user);
                $manager->persist($usersSubjects);
            }

            $manager->persist($user);

            $userSeason = new UserSeason();
            $userSeason->setUser($user);
            $userSeason->setSeason($season);
            $manager->persist($userSeason);
        }

//
        foreach ($subs as $idx=>$sub) {
            $usersSubjects = new UsersSubjects();
            $usersSubjects->setSeason($season);
            $usersSubjects->setStudyProgram($studyProgramINF);
            $usersSubjects->setSubject($sub);
            $usersSubjects->setUser($userFoo);
            $manager->persist($usersSubjects);
        }

        $userFoo->addRole($roleUser);

        $userAdmin->addRole($roleAdmin);

        $manager->persist($userFoo);
        $manager->persist($userAdmin);
        $manager->persist($roleAdmin);
        $manager->persist($roleUser);
        $manager->persist($roleSuperAdmin);

        $manager->flush();
    }
}
