<?php
/**
 * This file contains user provider for Anketa
 *
 * @copyright Copyright (c) 2011,2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Security
 * @author     Martin Sucha <anty.sk+svt@gmail.com>
 */

namespace AnketaBundle\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use AnketaBundle\Entity\User;
use AnketaBundle\Entity\UserSeason;

class AnketaUserProvider implements UserProviderInterface
{

    /** @var ContainerInterface */
    private $container;

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $userSources;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ContainerInterface $container, array $userSources, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->userSources = $userSources;
        $this->logger = $logger;
    }

    public function getUserSources()
    {
        return $this->userSources;
    }

    /**
     * Reload a user given an existing UserInterface instance.
     * (This happens on each request.)
     *
     * @param UserInterface $user to reload
     * @return User the reloaded user
     * @throws UnsupportedUserException if the UserInstance given is not User
     */
    public function refreshUser(UserInterface $oldUser)
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('refreshing user %s object %s', $oldUser->getUsername(), get_class($oldUser)));
        }
        if (!($oldUser instanceof User)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($oldUser)));
        }

        $user = $this->em->getRepository('AnketaBundle:User')
                ->findOneWithRolesByLogin($oldUser->getLogin());

        if ($user === null) {
            throw new UsernameNotFoundException(sprintf("User %s not found in database! Cannot refresh.", $oldUser->getLogin()));
        }

        $user->setOrgUnits($oldUser->getOrgUnits());
        $this->loadUserInfo($user);

        return $user;
    }

    /**
     * Load a user with given username. (This happens when the user logs in.)
     *
     * This function tries to load the user from database first.
     * If that is not successful, try to construct a user from
     * LDAP and AIS2, if enabled.
     *
     * @param string $username
     * @return User the requested user
     * @throws UsernameNotFoundException if the given user cannot be found
     *                                   nor constructed
     */
    public function loadUserByUsername($username)
    {
        // It seems that the username argument may also be a UserInterface instance
        // ... may be a bug in Symfony security component
        // (the user(name) is extracted from PreAuthenticatedToken at
        // PreAuthenticatedAuthenticationProvider:67)
        if ($username instanceof UserInterface) {
            $username = $username->getUsername();
        }
        $username = (string) $username;

        if ($this->logger) {
            $this->logger->debug(sprintf('AnketaUserProvider loading user %s', $username));
        }

        // Try to load the user from database first
        $user = $this->em->getRepository('AnketaBundle:User')
                ->findOneWithRolesByLogin($username);

        if ($user === null) {
            $user = new User($username);
            $this->em->persist($user);
            $this->em->flush($user);

            $user->addRole($this->em->getRepository('AnketaBundle:Role')
                    ->findOrCreateRole('ROLE_USER'));
        }

        $this->loadUserInfo($user, true);

        if ($user->getDisplayName() === null) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $user->getLogin()));
        }

        return $user;
    }

    /**
     * Load user info and user's subjects in necessary.
     *
     * @param User $user
     */
    private function loadUserInfo(User $user, $loadDisplayName = false)
    {
        $activeSeason = $this->em->getRepository('AnketaBundle:Season')
                ->getActiveSeason();
        $userSeason = $this->em->getRepository('AnketaBundle:UserSeason')
                ->findOneBy(array('user' => $user,
                                  'season' => $activeSeason));

        if ($userSeason === null) {
            $userSeason = new UserSeason();
            $userSeason->setUser($user);
            $userSeason->setSeason($activeSeason);
            $this->em->persist($userSeason);
        }

        // "$load[X][Y]" == "service X should load user attribute Y"
        $load = array();

        if ($loadDisplayName || $user->getDisplayName() === null) {
            $load[$this->userSources['displayName']]['displayName'] = true;
        }
        if (!$user->getOrgUnits()) {
            $load[$this->userSources['orgUnits']]['orgUnits'] = true;
        }

        foreach ($load as $service => $attributes) {
            $this->container->get($service)->load($userSeason, $attributes);
        }

        $this->em->flush();
    }

    /**
     * Check whether this provider supports given user class
     * @param string $class classname
     * @return boolean true iff the class is AnketaBundle\Entity\User
     */
    public function supportsClass($class)
    {
        return $class === 'AnketaBundle\Entity\User';
    }

}
