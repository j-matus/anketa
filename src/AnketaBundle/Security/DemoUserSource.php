<?php
/**
 * This file contains user source that assigns all subjects to the user,
 * and grants him a voting right. Useful for demo version, where we can't use
 * AIS to provide such information.
 *
 * @copyright Copyright (c) 2011,2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Security
 * @author     Martin Sucha <anty.sk+svt@gmail.com>
 */

namespace AnketaBundle\Security;

use Doctrine\ORM\EntityManager;
use AnketaBundle\Entity\UserSeason;
use AnketaBundle\Entity\UsersSubjects;

class DemoUserSource implements UserSourceInterface
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function load(UserSeason $userSeason, array $want)
    {
        $user = $userSeason->getUser();

        if (isset($want['displayName'])) {
            $name = $user->getUserName();
            $name = preg_replace('/[0-9]/', '', $name);
            $name = ucfirst($name) . ' Sapiens';
            $user->setDisplayName($name);
        }

        if (isset($want['isStudent'])) {
            $userSeason->setIsStudent(true);
        }

        if (isset($want['subjects'])) {
            $this->loadSubjects($userSeason);
        }
    }

    private function loadSubjects(UserSeason $userSeason)
    {
        $user = $userSeason->getUser();
        // prvych par predmetov a studijnych programov
        $subjects = $this->em->getRepository('AnketaBundle:Subject')->findBy(array(), null, 20);
        $studyPrograms = $this->em->getRepository('AnketaBundle:StudyProgram')->findBy(array(), null, 2);
        if (count($studyPrograms) == 0) {
            throw new \Exception('Chyba studijny program');
        }

        foreach ($subjects as $index => $subject) {
            $userSubject = new UsersSubjects();
            $userSubject->setUser($user);
            $userSubject->setSeason($userSeason->getSeason());
            $userSubject->setSubject($subject);
            $studyProgram = $studyPrograms[$index % count($studyPrograms)];
            $userSubject->setStudyProgram($studyProgram);
            $userSubject->setStudyYear(rand(1,5));

            $this->em->persist($userSubject);
        }
    }
}
