<?php
/**
 * This file contains user provider for Anketa
 *
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Security
 * @author     Martin Sucha <anty.sk+svt@gmail.com>
 */

namespace AnketaBundle\Integration;

use libfajr\trace\Trace;
use libfajr\connection\AIS2ServerConnection;
use libfajr\login\Login;
use libfajr\connection\HttpConnection;
use libfajr\window\studium\StudiumFactory;
use libfajr\window\studium\AdministraciaStudiaScreen;
use libfajr\window\AIS2MainScreen;
use libfajr\window\AIS2ApplicationEnum;

class AISRetriever
{

    /** @var AIS2ServerConnection */
    private $connection;

    /** @var Login */
    private $login;

    /** @var StudiumFactory */
    private $adminStudiaFactory;

    /** @var Trace */
    private $trace;

    /** @var AIS2MainScreen */
    private $mainScreen;

    /** @var AdministraciaStudiaScreen|null */
    private $adminStudiaScreen;

    /** @var array|null */
    private $studia;

    /** @var array|null */
    private $predmety;

    /** @var boolean */
    private $triedLogin;

    public function __construct(Trace $trace,
                                AIS2ServerConnection $connection,
                                Login $login,
                                AIS2MainScreen $mainScreen,
                                StudiumFactory $adminStudiaFactory)
    {
        $this->trace = $trace;
        $this->connection = $connection;
        $this->login = $login;
        $this->mainScreen = $mainScreen;
        $this->adminStudiaFactory = $adminStudiaFactory;
        $this->adminStudiaScreen = null;
        $this->studia = null;
        $this->predmety = null;
        $this->triedLogin = false;
    }

    public function __destruct() {
        if ($this->adminStudiaScreen !== null) {
            $this->adminStudiaScreen->closeWindow($this->trace);
        }
        $this->logoutIfNotAlready();
    }

    public function loginIfNotAlready() {
        if (!$this->login->isLoggedIn($this->connection)) {
            if (!$this->login->login($this->connection)) {
                throw new \Exception("AIS login failed");
            }
        }
        $this->triedLogin = true;
    }

    public function logoutIfNotAlready() {
        // Use lightweight check for login as we use lazy initialized
        // connection and login->isLoggedIn could force initialization.
        // Having triedLogin true means the connection must
        // have been initialized before as we already attempted to login
        if (!$this->triedLogin) return;

        if ($this->adminStudiaScreen !== null) {
            $this->adminStudiaScreen->closeWindow($this->trace);
        }

        if ($this->login->isLoggedIn($this->connection)) {
            $this->login->logout($this->connection);
        }
        $this->connection->getHttpConnection()->clearCookies();
    }

    private function getAdminStudiaScreen() {
        if ($this->adminStudiaScreen !== null) {
            return $this->adminStudiaScreen;
        }

        $this->adminStudiaScreen = $this->adminStudiaFactory->newAdministraciaStudiaScreen($this->trace);
        return $this->adminStudiaScreen;
    }

    public function getStudia()
    {
        if ($this->studia !== null) {
            return $this->studia;
        }

        $this->loginIfNotAlready();
        $zoznamStudii = $this->getAdminStudiaScreen()->getZoznamStudii($this->trace);
        $this->studia = $zoznamStudii->getData();
        return $this->studia;
    }

    /**
     * Get a list of subjects
     *
     * @param array(array(string(rok/rok),string(Z/L)))
     *        $semestre a list of semesters to return or null if all are to
     *                  be returned. Default is to return subjects for all
     *                  semesters.
     *
     * @return array(array()) a list of subjects
     */
    public function getPredmety(array $semestre = null)
    {
        if ($this->predmety !== null) {
            return $this->predmety;
        }

        $this->loginIfNotAlready();
        $adminStudiaScreen = $this->getAdminStudiaScreen();
        $studia = $this->getStudia();

        $vsetky_predmety = array();

        if ($semestre !== null) {
            $roky = array();
            foreach ($semestre as $semester) {
                if (!array_key_exists($semester[0], $roky)) {
                    $roky[$semester[0]] = array();
                }
                $roky[$semester[0]][] = $semester[1];
            }
        }

        foreach ($studia as $studium => $studiumInfo) {

            $zapisneListy = $adminStudiaScreen->getZapisneListy($this->trace, $studium)->getData();

            foreach ($zapisneListy as $zapisnyList => $zapisnyListInfo) {
                $akadRok = $zapisnyListInfo['popisAkadRok'];
                if ($semestre !== null && !array_key_exists($akadRok, $roky)) continue;

                $hodnoteniaPriemeryScreen = $this->adminStudiaFactory->
                        newHodnoteniaPriemeryScreen($this->trace,
                        $adminStudiaScreen->getParamNameFromZapisnyListIndex($this->trace, $zapisnyList,
                            AdministraciaStudiaScreen::ACTION_HODNOTENIA_PRIEMERY));

                $hodnotenia = $hodnoteniaPriemeryScreen->getHodnotenia($this->trace)->getData();

                foreach ($hodnotenia as $hodnotenie) {
                    if ($semestre !== null && !in_array($hodnotenie['semester'], $roky[$akadRok])) continue;
                    $hodnotenie['akRok'] = $akadRok;
                    $hodnotenie['rokStudia'] = $studiumInfo['rokStudia'];
                    $hodnotenie['studijnyProgram'] = array('skratka' => $studiumInfo['studijnyProgramSkratka'],
                        'nazov' => $studiumInfo['studijnyProgramPopis']);
                    $vsetky_predmety[] = $hodnotenie;
                }

                $hodnoteniaPriemeryScreen->closeWindow($this->trace);
            }
        }

        $this->predmety = $vsetky_predmety;
        return $this->predmety;
    }

    public function getFullName()
    {
        $this->loginIfNotAlready();
        return $this->mainScreen->getFullUserName($this->trace);
    }

    public function isAdministraciaStudiaAllowed()
    {
        $this->loginIfNotAlready();
        $apps = $this->mainScreen->getAllAvailableApplications($this->trace, array('ES'));
        return in_array(AIS2ApplicationEnum::ADMINISTRACIA_STUDIA, $apps);
    }

}
