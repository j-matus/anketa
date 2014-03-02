<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Entity__Repository
 * @author     Jakub Marek <jakub.marek@gmail.com>
 */

namespace AnketaBundle\Entity;

use Doctrine\ORM\EntityRepository;

class DepartmentRepository extends EntityRepository {
    public function getDepartmentAuthorizedUsers(Department $department, Season $season){
        $em = $this->getEntityManager();
        $repository = $em->getRepository('AnketaBundle:User');

        $authorizedPeople = array();
        foreach( $repository->findUsersWithAnyRole(array("ROLE_DEPARTMENT_REPORT")) as $user){
            $userDepartment = $repository->getUserDepartment($user, $season);

            if($userDepartment == $department)
                $authorizedPeople[] = $user;
        }

        return $authorizedPeople;
    }
}
