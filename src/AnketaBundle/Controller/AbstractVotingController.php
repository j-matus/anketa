<?php

namespace AnketaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AbstractVotingController extends Controller {

    public function preExecute() {
        $access = $this->get('anketa.access.hlasovanie');
        $user = $access->getUser();
        $userSeason = $access->getUserSeason();
        if ($user === null || $userSeason === null) throw new AccessDeniedException();

        if (!$userSeason->getLoadedFromAis()) {
            $userSeason->setLoadedFromAis(TRUE);
            $this->get('anketa.user_provider')->loadUserInfo($user, array('isStudentThisSeason', 'subjects'));
        }

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
}
