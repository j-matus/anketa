<?php

/**
 * @copyright Copyright (c) 2011,2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Entity
 */

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AnketaBundle\Entity\SeasonRepository")
 */
class Season {

    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Full name, i.e. 2010/2011
     * @ORM\Column(type="string", nullable=false)
     */
    protected $description;

    /**
     * Total number of students in this season
     * @ORM\Column(type="integer")
     * @var int $studentCount
     */
    protected $studentCount;

    /**
     * Slug - unique descriptive ID to be used in URLs.
     *
     * For example 2010-2011
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     * @var string $slug
     */
    protected $slug;

    /**
     * Marks active season.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @var boolean $active
     */
    protected $active = false;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $votingOpen;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $respondingOpen;

    /**
     * The level of results information available to logged out users.
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $levelPublic;

    /**
     * The level of results information available to logged in users.
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $levelUniversity;

    /**
     * The level of results information available to students of this faculty.
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $levelFacultyStudent;

    /**
     * The level of results information available to teachers of this faculty.
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $levelFacultyTeacher;

    const LEVEL_NOTHING   = 0; // no results visible
    const LEVEL_NUMBERS   = 1; // numbers and graphs visible
    const LEVEL_COMMENTS  = 2; // text comments visible
    const LEVEL_RESPONSES = 3; // teacher responses visible

    /**
     * TODO: This is a huge hack and needs to be removed as soon as it's not
     * needed.
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $fafRestricted;

    /**
     * Order seasons by this column in descending order in results.
     * The larger the number, the later in history the season will appear.
     * 
     * @ORM\Column(type="integer", nullable=false)
     * @var int $ordering
     */
    protected $ordering;

    /**
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var string $endTime;
     */
    protected $endTime;

    /**
     * Comma-separated semester list. Each item is given as the year and the semester (Z/L).
     * If the semester is not specified, the item will match subjects that don't have a semester in AIS
     * (i.e. subjects that take the whole year).
     * Example: 2010/2011Z,2010/2011L,2010/2011
     * @ORM\Column(type="string")
     */
    protected $aisSemesters = '';

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string $officialStatement;
     */
    protected $officialStatement;

    /**
     * When to hide a subject, based on which of its teachers are hidden.
     *
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $subjectHiding = 0;
    const HIDE_SUBJECT_NEVER  = 0;
    const HIDE_SUBJECT_IF_ANY = 1;
    const HIDE_SUBJECT_IF_ALL = 2;

    /**
     * If we want to ignore hideAllResults.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @var boolean $showAllResults
     */
    protected $showAllResults = false;

    public function __construct($description, $slug) {
        $this->setDescription($description);
        $this->setSlug($slug);
    }

    public function getId() {
        return $this->id;
    }

    public function setDescription($value) {
        $this->description = $value;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getStudentCount() {
        return $this->studentCount;
    }

    public function setStudentCount($studentCount) {
        $this->studentCount = $studentCount;
    }

    public function getSlug() {
        return $this->slug;
    }

    public function setSlug($value) {
        $this->slug = $value;
    }

    /**
     * @return boolean whether the season is active
     */
    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public function getShowAllResults() {
        return $this->showAllResults;
    }

    public function setShowAllResults($showAllResults) {
        $this->showAllResults = $showAllResults;
    }

    public function getVotingOpen() {
        return $this->votingOpen;
    }

    public function setVotingOpen($value) {
        $this->votingOpen = $value;
    }

    public function getRespondingOpen() {
        return $this->respondingOpen;
    }

    public function setRespondingOpen($value) {
        $this->respondingOpen = $value;
    }

    public function getLevelPublic() {
        return $this->levelPublic;
    }

    public function setLevelPublic($value) {
        $this->levelPublic = $value;
    }

    public function getLevelUniversity() {
        return $this->levelUniversity;
    }

    public function setLevelUniversity($value) {
        $this->levelUniversity = $value;
    }

    public function getLevelFacultyStudent() {
        return $this->levelFacultyStudent;
    }

    public function setLevelFacultyStudent($value) {
        $this->levelFacultyStudent = $value;
    }

    public function getLevelFacultyTeacher() {
        return $this->levelFacultyTeacher;
    }

    public function setLevelFacultyTeacher($value) {
        $this->levelFacultyTeacher = $value;
    }

    public function getFafRestricted() {
        return $this->fafRestricted;
    }

    public function setFafRestricted($value) {
        $this->fafRestricted = $value;
    }

    public function getOrdering() {
        return $this->ordering;
    }

    public function setOrdering($ordering) {
        $this->ordering = $ordering;
    }

    public function getEndTime() {
        return $this->endTime;
    }

    public function setEndTime($endTime) {
        $this->endTime = $endTime;
    }

    /**
     * @return array(array(year,semester))
     */
    public function getAisSemesterList() {
        $result = array();
        if ($this->aisSemesters) {
            foreach (explode(',', $this->aisSemesters) as $item) {
                if (!preg_match('@^[0-9]{4}/[0-9]{4}[ZL]?$@', $item)) {
                    throw new \Exception('Wrong aisSemesters value');
                }
                $schoolYear = substr($item, 0, -1);
                $semester = substr($item, -1);
                if ($semester != 'Z' && $semester != 'L') {
                    // matches year-round subjects that don't have a semester
                    $schoolYear = $item;
                    $semester = '';
                }
                $result[] = array($schoolYear, $semester);
            }
        }
        return $result;
    }

    public function getOfficialStatement() {
        return $this->officialStatement;
    }

    public function setOfficialStatement($officialStatement) {
        $this->officialStatement = $officialStatement;
    }

    public function getSubjectHiding() {
        return $this->subjectHiding;
    }

    public function setSubjectHiding($subjectHiding) {
        if ($subjectHiding !== HIDE_SUBJECT_NEVER &&
            $subjectHiding !== HIDE_SUBJECT_IF_ANY &&
            $subjectHiding !== HIDE_SUBJECT_IF_ALL) {
            throw \UnexpectedValueException();
        }
        $this->subjectHiding = $subjectHiding;
    }

}
