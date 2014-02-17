<?php
/**
 * @copyright Copyright (c) 2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Entity
 * @author     Martin Sucha
 */

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AnketaBundle\Entity\Subject;
use AnketaBundle\Entity\Season;

/**
 * @ORM\Entity()
 * @ORM\Table(name="SubjectSeason",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="subject_season_unique",columns={"subject_id","season_id"})})
 */
class SubjectSeason {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Subject")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Subject $subject
     */
    protected $subject;

    /**
     * @ORM\ManyToOne(targetEntity="Season")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Season $season
     */
    protected $season;

    public function getSubject() {
        return $this->subject;
    }

    /**
     * Pocet studentov fakulty, ktori mali zapisany tento predmet
     * danu sezonu.
     * @ORM\Column(type="integer", nullable=true)
     * @param int $studentCountFacutlty
     */
    protected $studentCountFaculty;

    /**
     * Pocet studentov, ktori mali tento predmet zapisany celkovo
     * (t.j. sem sa rata aj napr. niekto z managementu, kto mal zapisany
     *  predmet na matfyze)
     * @ORM\Column(type="integer", nullable=true)
     * @var int $studentCountAll
     */
    protected $studentCountAll;

    /**
     * Pocet studentov, ktori mali zapisany tento predmet a
     * mali s neho A.
     * @ORM\Column(type="integer", nullable=true)
     * @param int $aCount
     */
    protected $aCount;

    /**
     * Pocet studentov, ktori mali zapisany tento predmet a
     * mali s neho B.
     * @ORM\Column(type="integer", nullable=true)
     * @param int $bCount
     */
    protected $bCount;

    /**
     * Pocet studentov, ktori mali zapisany tento predmet a
     * mali s neho C.
     * @ORM\Column(type="integer", nullable=true)
     * @param int $cCount
     */
    protected $cCount;

    /**
     * Pocet studentov, ktori mali zapisany tento predmet a
     * mali s neho D.
     * @ORM\Column(type="integer", nullable=true)
     * @param int $dCount
     */
    protected $dCount;

    /**
     * Pocet studentov, ktori mali zapisany tento predmet a
     * mali s neho E.
     * @ORM\Column(type="integer", nullable=true)
     * @param int $eCount
     */
    protected $eCount;

    /**
     * Pocet studentov, ktori mali zapisany tento predmet a
     * mali s neho Fx.
     * @ORM\Column(type="integer", nullable=true)
     * @param int $fxCount
     */
    protected $fxCount;

    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function getSeason() {
        return $this->season;
    }

    public function setSeason($season) {
        $this->season = $season;
    }

    public function getId() {
        return $this->id;
    }

    public function getStudentCountFaculty() {
        return $this->studentCountFaculty;
    }

    public function setStudentCountFaculty($studentCountFaculty) {
        $this->studentCountFaculty = $studentCountFaculty;
    }

    public function getStudentCountAll() {
        return $this->studentCountAll;
    }

    public function setStudentCountAll($studentCountAll) {
        $this->studentCountAll = $studentCountAll;
    }

    public function getACount() {
        return $this->aCount;
    }

    public function setACount($aCount) {
        $this->aCount = $aCount;
    }

    public function getBCount() {
        return $this->bCount;
    }

    public function setBCount($bCount) {
        $this->bCount = $bCount;
    }

    public function getCCount() {
        return $this->cCount;
    }

    public function setCCount($cCount) {
        $this->aCount = $cCount;
    }

    public function getDCount() {
        return $this->dCount;
    }

    public function setDCount($dCount) {
        $this->dCount = $dCount;
    }

    public function getECount() {
        return $this->eCount;
    }

    public function setECount($eCount) {
        $this->eCount = $eCount;
    }

    public function getFxCount() {
        return $this->aCount;
    }

    public function setFxCount($fxCount) {
        $this->fxCount = $fxCount;
    }
}
