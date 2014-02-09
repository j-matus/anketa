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

    const PERMISSION_NONE = 0;
    const PERMISSION_NUMERIC = 1;
    const PERMISSION_NUMERIC_TEXT = 2;
    const PERMISSION_NUMERIC_TEXT_RESPONSES = 3;

    protected static $permissions = array(
        self::PERMISSION_NONE,
        self::PERMISSION_NUMERIC,
        self::PERMISSION_NUMERIC_TEXT,
        self::PERMISSION_NUMERIC_TEXT_RESPONSES
    );

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
     * TODO: This is a huge hack and needs to be removed as soon as it's not
     * needed.
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $fafRestricted;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $permissionsPublic = 0;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $permissionsUniversity = 0;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $permissionsFaculty = 0;

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
     * @ORM\Column(type="string", nullable=true)
     * @var string $officialStatement;
     */
    protected $officialStatement;

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

    private function validatePermission($value) {
        return in_array($value, $this->permissions);
    }

    public function setPermissionsPublic($value) {
        if ($this->validatePermission($value)) {
            $this->permissionsPublic = $value;
        } else {
            throw new \UnexpectedValueException();
        }
    }

    public function getPermissionsPublic() {
        return $this->permissionsPublic;
    }

    public function setPermissionsUniversity($value) {
        if ($this->validatePermission($value)) {
            $this->permissionsUniversity = $value;
        } else {
            throw new \UnexpectedValueException();
        }
    }

    public function getPermissionsUniversity() {
        return $this->permissionsUniversity;
    }

    public function setPermissionsFaculty($value) {
        if ($this->validatePermission($value)) {
            $this->permissionsFaculty = $value;
        } else {
            throw new \UnexpectedValueException();
        }
    }

    public function getPermissionsFaculty() {
        return $this->permissionsFaculty;
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

    public function getOfficialStatement() {
        return $this->officialStatement;
    }

    public function setOfficialStatement($officialStatement) {
        $this->officialStatement = $officialStatement;
    }

}
