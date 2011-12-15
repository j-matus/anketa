<?php
/**
 * This file contains user source interface
 *
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Security
 * @author     Martin Sucha <anty.sk+svt@gmail.com>
 */

namespace AnketaBundle\Security;

use Doctrine\ORM\EntityManager;
use AnketaBundle\Entity\User;
use AnketaBundle\Entity\Subject;
use AnketaBundle\Integration\AISRetriever;
use AnketaBundle\Entity\Role;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class AISUserSource implements UserSourceInterface
{

    /**
     * Doctrine repository for Subject entity
     * @var AnketaBundle\Entity\SubjectRepository
     */
    private $subjectRepository;
    
    /**
     * Doctrine repository for StudyProgram entity
     * @var AnketaBundle\Entity\StudyProgramRepository
     */
    private $studyProgramRepository;

    /**
     * Doctrine repository for Role entity
     * @var AnketaBundle\Entity\RoleRepository
     */
    private $roleRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var Connection */
    private $dbConn;

    /** @var AISRetriever */
    private $aisRetriever;

    /** @var array(array(rok,semester)) */
    private $semestre;

    /** @var boolean */
    private $loadAuth;
    
    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $dbConn, EntityManager $em, AISRetriever $aisRetriever,
                                array $semestre, $loadAuth, LoggerInterface $logger = null)
    {
        $this->dbConn = $dbConn;
        $this->entityManager = $em;
        $this->subjectRepository = $em->getRepository('AnketaBundle:Subject');
        $this->roleRepository = $em->getRepository('AnketaBundle:Role');
        $this->studyProgramRepository = $em->getRepository('AnketaBundle:StudyProgram');
        $this->aisRetriever = $aisRetriever;
        $this->semestre = $semestre;
        $this->loadAuth = $loadAuth;
        $this->logger = $logger;
    }

    public function load(UserBuilder $builder)
    {
        if (!$builder->hasFullName()) {
            $builder->setFullName($this->aisRetriever->getFullName());
        }
        
        if ($this->aisRetriever->isAdministraciaStudiaAllowed()) {
            $this->loadSubjects($builder);

            if ($this->loadAuth) {            
                $builder->addRole($this->roleRepository->findOrCreateRole('ROLE_AIS_STUDENT'));
                $builder->markStudent();
            }
        }

        $this->aisRetriever->logoutIfNotAlready();
    }

    /**
     * Load subject entities associated with this user
     */
    private function loadSubjects(UserBuilder $builder)
    {
        $aisPredmety = $this->aisRetriever->getPredmety($this->semestre);
        
        $kody = array();

        foreach ($aisPredmety as $aisPredmet) {
            $dlhyKod = $aisPredmet['skratka'];
            $kratkyKod = $this->getKratkyKod($dlhyKod);
            
            // Ignorujme duplicitne predmety
            if (in_array($kratkyKod, $kody)) {
                continue;
            }
            $kody[] = $kratkyKod;

            // vytvorime subject v DB ak neexistuje
            // pouzijeme INSERT ON DUPLICATE KEY UPDATE
            // aby sme nedostavali vynimky pri raceoch
            $stmt = $this->dbConn->prepare("INSERT INTO Subject (code, name) VALUES (:code, :name) ON DUPLICATE KEY UPDATE code=code");
            $stmt->bindValue('code', $kratkyKod);
            $stmt->bindValue('name', $aisPredmet['nazov']);
            $stmt->execute();

            $subject = $this->subjectRepository->findOneBy(array('code' => $kratkyKod));
            if ($subject == null) {
                throw new \Exception("Nepodarilo sa pridať predmet do DB");
            }
            $stmt = null;
            
            // Vytvorime studijny program v DB ak neexistuje
            // podobne ako predmet vyssie
            $stmt = $this->dbConn->prepare("INSERT INTO StudyProgram (code, name, slug) VALUES (:code, :name, :slug) ON DUPLICATE KEY UPDATE code=code");
            $stmt->bindValue('code', $aisPredmet['studijnyProgram']['skratka']);
            $stmt->bindValue('name', $aisPredmet['studijnyProgram']['nazov']);
            // TODO(anty): toto nezarucuje, ze to je vhodny string
            // treba pouzivat whitelist namiesto blacklistu!
            $stmt->bindValue('slug', $this->generateSlug($aisPredmet['studijnyProgram']['skratka']));
            $stmt->execute();

            $studyProgram = $this->studyProgramRepository->findOneBy(array('code' => $aisPredmet['studijnyProgram']['skratka']));
            if ($studyProgram == null) {
                throw new \Exception("Nepodarilo sa pridať študijný program do DB");
            }
            $stmt = null;

            $builder->addSubject($subject, $studyProgram);
        }
    }

    /**
     * @todo presunut do samostatnej triedy a spravit lepsie
     */
    private function generateSlug($slug)
    {
        $slug = str_replace(array(' ', '/'),'-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    private function getKratkyKod($dlhyKod)
    {
        $matches = array();
        if (preg_match('@^[^/]*/([^/]+)/@', $dlhyKod, $matches) !== 1) {
            // Sice nevieme zistit kratky kod,
            // to ale neznamena, ze k tomu predmetu nemozu hlasovat
            // kazdopadne si to ale chceme zalogovat
            if ($this->logger !== null) {
                $this->logger->warn('Nepodarilo sa zistit kratky kod predmetu',
                        array('dlhyKod'=>$dlhyKod));
            }
            return $dlhyKod;
        }

        $kratkyKod = $matches[1];
        return $kratkyKod;
    }

}
