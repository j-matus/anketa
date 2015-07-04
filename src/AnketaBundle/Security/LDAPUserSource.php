<?php
/**
 * This file contains LDAP user source implementation
 *
 * @copyright Copyright (c) 2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Security
 * @author     Martin Sucha <anty.sk+svt@gmail.com>
 */

namespace AnketaBundle\Security;

use AnketaBundle\Integration\LDAPRetriever;
use AnketaBundle\Entity\UserSeason;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class LDAPUserSource implements UserSourceInterface
{

    /** @var EntityManager */
    private $em;

    /** @var LDAPRetriever */
    private $ldapRetriever;

    /** @var string */
    private $orgUnit;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManager $em, LDAPRetriever $ldapRetriever, $orgUnit, LoggerInterface $logger = null)
    {
        $this->em = $em;
        $this->ldapRetriever = $ldapRetriever;
        $this->orgUnit = $orgUnit;
        $this->logger = $logger;
    }

    public function load(UserSeason $userSeason, array $want)
    {
        $user = $userSeason->getUser();
        $uidFilter = '(uid=' . $this->ldapRetriever->escape($user->getLogin()) . ')';

        if ($this->logger !== null) {
            $this->logger->info(sprintf('LDAP search with filter: %s', $uidFilter));
        }

        $this->ldapRetriever->loginIfNotAlready();
        try {
            $userInfo = $this->ldapRetriever->searchOne($uidFilter, array('group', 'displayName'));
            $this->ldapRetriever->logoutIfNotAlready();
        }
        catch(\Exception $e) {
            $this->ldapRetriever->logoutIfNotAlready();
            throw $e;
        }

        if ($userInfo === null) {
            if ($this->logger !== null) {
                $this->logger->info(sprintf('User %s not found in LDAP', $user->getLogin()));
            }
            return;
            // AnketaUserProvider will throw UsernameNotFoundException if there's no displayName
        }
        
        if (isset($want['displayName']) && !empty($userInfo['displayName'])) {
            $user->setDisplayName($userInfo['displayName'][0]);
        }

        foreach ($userInfo['group'] as $group) {
            if ($group == 'studenti_' . $this->orgUnit) {
                $user->addRole($this->em->getRepository('AnketaBundle:Role')
                        ->findOrCreateRole('ROLE_STUDENT_AT_ANY_TIME'));
            }
        }
    }

}
