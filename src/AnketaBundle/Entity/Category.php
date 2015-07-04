<?php

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use AnketaBundle\Lib\Preconditions;

/**
 * @ORM\Entity(repositoryClass="AnketaBundle\Entity\CategoryRepository")
 */
class Category {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Defaults to 100
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $position;

    /**
     * The type of the category, i.e. "general", "subject"
     * @ORM\Column(type="string", nullable=false)
     */
    protected $type;

    /**
     * Subcategory of category,
     * @ORM\Column(type="string", unique=true)
     */
    protected $specification;

    /**
     * Describes the subcategory, i.e. School properties/Food for students
     * If no subcategories are needed, it's the same as main category.
     * @ORM\Column(type="string")
     */
    protected $description;

    /**
     * Description in English.
     * @ORM\Column(type="string")
     */
    protected $description_en;

    /**
     * @ORM\OneToMany(targetEntity="Question", mappedBy="category")
     * @ORM\OrderBy({"position" = "ASC"})
     *
     * @var ArrayCollection $questions
     */
    protected $questions;

    public function __construct($type, $specification, $description = null, $description_en = null) {
        $this->questions = new ArrayCollection();
        $this->setType($type);
        $this->setDescription($description, 'sk');
        $this->setDescription($description_en, 'en');
        $this->setSpecification($specification);
        // viac ako 100 otazok dufam nikdy nebudeme zobrazovat na 1 stranke
        $this->position = 100;
    }

    public function getId() {
        return $this->id;
    }

    public function setPosition($value) {
        $this->position = $value;
    }

    public function getPosition() {
        return $this->position;
    }

    public function setType($value) {
        Preconditions::check(CategoryType::isValid($value));
        $this->type = $value;
    }

    public function getType() {
        return $this->type;
    }

    public function setDescription($value, $lang = 'sk') {
        Preconditions::check($value == null || is_string($value));
        if ($lang == 'en') {
            $this->description_en = $value;
        } else {
            $this->description = $value;
        }
    }

    public function getDescription($lang = 'sk') {
        if ($lang == 'en') {
          if ($this->description_en != "") {
              return $this->description_en;
          }
        }
        return $this->description;
    }

    /**
     * @param ArrayCollection $value
     */
    public function setQuestions($value) {
        $this->questions = $value;
    }

    /**
     * @param Question $value
     */
    public function addQuestion($value) {
        $this->questions[] = $value;
    }

    /**
     * @return ArrayCollection questions
     */
    public function getQuestions() {
        return $this->questions;
    }

    public function getSpecification() {
        return $this->specification;
    }

    public function setSpecification($specification) {
        $this->specification = $specification;
    }


}
