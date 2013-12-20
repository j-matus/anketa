<?php

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use libfajr\base\Preconditions;

/**
 * @ORM\Entity()
 */
class TeachingAssociation {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Season")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Season $season
     */
    protected $season;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User $requestedBy
     */
    protected $requestedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Subject")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Subject $subject
     */
    protected $subject;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User $teacher
     */
    protected $teacher;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @var string $note
     */
    protected $note;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @var boolean ci prednasa(l) k danemu predmetu
     */
    protected $lecturer;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @var boolean ci cvici(l)
     */
    protected $trainer;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @var boolean ci bola tato poziadavka vybavena
     */
    protected $completed;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    protected $createdOn;

    /**
     * @param String $name
     */
    public function __construct(Season $season, Subject $subject,
            User $teacher = NULL, User $requestedBy = NULL, $note = '',
            $lecturer = NULL, $trainer = NULL, $completed = FALSE,
            $createdOn = NULL) {
        Preconditions::checkIsString($note, 'note must be string');
        $this->requestedBy = $requestedBy;
        $this->teacher = $teacher;
        $this->subject = $subject;
        $this->season = $season;
        $this->note = $note;
        $this->lecturer = $lecturer;
        $this->trainer = $trainer;
        $this->completed = $completed;
        $this->createdOn = $createdOn;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getSeason() {
        return $this->season;
    }

    public function setSeason(Season $season) {
        $this->season = $season;
    }

    public function getRequestedBy() {
        return $this->requestedBy;
    }

    public function setRequestedBy(User $requestedBy = null) {
        $this->requestedBy = $requestedBy;
    }

    public function getSubject() {
        return $this->subject;
    }

    public function setSubject(Subject $subject) {
        $this->subject = $subject;
    }

    public function getTeacher() {
        return $this->teacher;
    }

    public function setTeacher(User $teacher = null) {
        $this->teacher = $teacher;
    }

    public function getNote() {
        return $this->note;
    }

    public function setNote($note) {
        Preconditions::checkIsString($note, 'note must be string');
        $this->note = $note;
    }

    public function getLecturer() {
        return $this->lecturer;
    }
    /**
     * @param bool $lecturer
     */
    public function setLecturer($lecturer) {
        $this->lecturer = $lecturer;
    }

    public function getTrainer() {
        return $this->trainer;
    }
    /**
     * @param bool $trainer
     */
    public function setTrainer($trainer) {
        $this->trainer = $trainer;
    }

    public function getCompleted() {
        return $this->completed;
    }
    /**
     * @param bool $completed
     */

    public function setCompleted($completed) {
        $this->completed = $completed;
    }

    public function getCreatedOn() {
        return $this->createdOn;
    }
    /**
     * @param bool $createdOn
     */
    public function setCreatedOn($createdOn) {
        $this->createdOn = $createdOn;
    }
}
