<?php

namespace AnketaBundle\Entity;

use AnketaBundle\Lib\Preconditions;

class CategoryType {
    const GENERAL = "general";
    const SUBJECT = "subject";
    const TEACHER_SUBJECT = "subject_teacher";
    const STUDY_PROGRAMME = "studijnyprogram";

    public static function isValid($type) {
        Preconditions::checkIsString($type);
        $allowed = array(
                self::GENERAL,
                self::SUBJECT,
                self::TEACHER_SUBJECT,
                self::STUDY_PROGRAMME);
        return in_array($type, $allowed);
    }
}

