<?php
/**
 * @copyright Copyright (c) 2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Access
 */

namespace AnketaBundle\Access;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContextInterface;

abstract class AbstractAccess
{
    /** @var SecurityContextInterface */
    protected $security;

    /** @var EntityManager */
    protected $em;

    /** @var string */
    private $allowedOrgUnit;

    /** @var mixed */
    protected $user;

    public function __construct(SecurityContextInterface $security, EntityManager $em, $allowedOrgUnit)
    {
        $this->security = $security;
        $this->em = $em;
        $this->user = null;
        $this->allowedOrgUnit = $allowedOrgUnit;
    }

    /**
     * Returns the logged in user, or null if nobody is logged in.
     *
     * @return mixed
     */
    public function getUser()
    {
        if ($this->user === null && $this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $token = $this->security->getToken();
            if ($token) $this->user = $token->getUser();
        }
        return $this->user;
    }

    /**
     * Returns whether the current user belongs to the allowed faculty (org. unit).
     *
     * @return boolean
     */
    public function isUserFromFaculty()
    {
        return $this->getUser() && $this->getUser()->hasOrgUnit($this->allowedOrgUnit);
    }
}

