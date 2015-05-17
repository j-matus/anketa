<?php

namespace AnketaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AbstractVotingController extends Controller {

    public function preExecute() {
        $access = $this->get('anketa.access.hlasovanie');
        if ($access->getUser() === null) throw new AccessDeniedException();
        if ($access->getUserSeason() === null) throw new AccessDeniedException();

        $this->loadUserInfo();

        if ($access->userCanVote()) return;

        if (!$access->isVotingOpen()) throw new AccessDeniedException();

        if (!$access->userIsStudent()) {
            $rv = $this->render('AnketaBundle:Hlasovanie:novote.html.twig');
            $rv->setStatusCode(403);
            return $rv;
        }

        if ($access->getUserSeason()->getFinished()) {
            $rv = $this->render('AnketaBundle:Hlasovanie:dakujeme.html.twig');
            $rv->setStatusCode(403);
            return $rv;
        }

        // Nemoze hlasovat z nejakeho ineho dovodu...
        throw new AccessDeniedException();
    }

    /**
     * Load user info and subjects if necessary.
     */
    private function loadUserInfo()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $user = $this->get('security.context')->getToken()->getUser();
        $activeSeason = $em->getRepository('AnketaBundle:Season')
                ->getActiveSeason();
        $userSeason = $em->getRepository('AnketaBundle:UserSeason')
                ->findOneBy(array('user' => $user,
                                  'season' => $activeSeason));
        $userSources = $this->get('anketa.user_provider')->getUserSources();

        // "$load[X][Y]" == "service X should load user attribute Y"
        $load = array();

        if (!$userSeason->getLoadedFromAis()) {
            $load[$userSources['isStudent']]['isStudent'] = TRUE;
            $load[$userSources['subjects']]['subjects'] = TRUE;
            $userSeason->setLoadedFromAis(TRUE);
        }

        foreach ($load as $service => $attributes) {
            $this->get($service)->load($userSeason, $attributes);
        }

        $em->flush();
    }
}
