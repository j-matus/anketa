<?php

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AnketaBundle\Entity\AnswerRepository")
 */
class Answer {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $attended = false;

    /**
     * @ORM\ManyToOne(targetEntity="Question")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Question $question
     */
    protected $question;

    /**
     * @ORM\ManyToOne(targetEntity="Season")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Season $season
     */
    protected $season;

    /**
     * @ORM\ManyToOne(targetEntity="Option")
     *
     * @var Option $option
     */
    protected $option;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User $teacher
     */
    protected $teacher;

    /**
     * @ORM\ManyToOne(targetEntity="Subject")
     *
     * @var Subject $subject
     */
    protected $subject;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User $user
     */
    protected $author;

    /**
     * @ORM\ManyToOne(targetEntity="StudyProgram")
     *
     * @var StudyProgram $studyProgram
     */
    protected $studyProgram;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $studyYear;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $inappropriate = false;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $reviewed = false;

    public function getId() {
        return $this->id;
    }

    public function setComment($value) {
        $this->comment = $value;
    }

    public function getComment() {
        return $this->comment;
    }

    public function hasComment() {
        return !empty($this->comment);
    }

    public function setAttended($value) {
        $this->attended = $value;
    }

    public function getAttended() {
        return $this->attended;
    }

    public function setInappropriate($value) {
        $this->inappropriate = $value;
    }

    public function getInappropriate() {
        return $this->inappropriate;
    }


    /**
     * @param Question $value
     */
    public function setQuestion($value) {
        $this->question = $value;
    }

    /**
     * @return Question the question
     */
    public function getQuestion() {
        return $this->question;
    }

    /**
     * @param Option $value
     */
    public function setOption($value) {
        $this->option = $value;
    }

    /**
     * @return Option the option
     */
    public function getOption() {
        return $this->option;
    }

    /**
     * @return Boolean true if Answer has option set
     */
    public function hasOption() {
        return !empty($this->option);
    }

    /**
     * @param User $value
     */
    public function setTeacher($value) {
        $this->teacher = $value;
    }

    /**
     * @return User the teacher
     */
    public function getTeacher() {
        return $this->teacher;
    }

    /**
     * @param Subject $value
     */
    public function setSubject($value) {
        $this->subject = $value;
    }

    /**
     * @return Subject the subject
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @param User $value
     */
    public function setAuthor($value) {
        $this->author = $value;
    }

    /**
     * @return User the author
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * @param StudyProgram $value
     */
    public function setStudyProgram($value) {
        $this->studyProgram = $value;
    }

    /**
     * @return StudyProgram study program
     */
    public function getStudyProgram() {
        return $this->studyProgram;
    }

    /**
     * @return integer study year
     */
    public function getStudyYear() {
        return $this->studyYear;
    }

    public function setStudyYear($value) {
        return $this->studyYear = $value;
    }

    public function getSeason() {
        return $this->season;
    }

    public function setSeason($season) {
        $this->season = $season;
    }

    public function setReviewed($reviewed) {
        $this->reviewed = $reviewed;
    }

    public function getReviewed() {
        return $this->reviewed;
    }
}
