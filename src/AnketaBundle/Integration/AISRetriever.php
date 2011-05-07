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

use fajr\libfajr\pub\base\Trace;
use fajr\libfajr\pub\connection\AIS2ServerConnection;
use fajr\libfajr\pub\login\Login;
use fajr\libfajr\pub\connection\HttpConnection;
use fajr\libfajr\pub\window\VSES017_administracia_studia\VSES017_Factory;
use fajr\libfajr\pub\window\VSES017_administracia_studia\AdministraciaStudiaScreen;
use fajr\libfajr\pub\window\AIS2MainScreen;
use fajr\libfajr\pub\window\AIS2ApplicationEnum;

class AISRetriever
{

    /** @var AIS2ServerConnection */
    private $connection;

    /** @var Login */
    private $login;

    /** @var VSES017_Factory */
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
                                VSES017_Factory $adminStudiaFactory)
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
            $this->adminStudiaScreen->closeIfNeeded($this->trace);
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

    public function getPredmety()
    {
        if ($this->predmety !== null) {
            return $this->predmety;
        }

        $this->loginIfNotAlready();
        $adminStudiaScreen = $this->getAdminStudiaScreen();
        $studia = $this->getStudia();

        $vsetky_predmety = array();

        foreach ($studia as $studium => $studiumInfo) {
            $prehladKreditovDialog = $adminStudiaScreen->
                getPrehladKreditovDialog($this->trace, $studium);
            $predmety = $prehladKreditovDialog->getPredmety($this->trace);
            $prehladKreditovDialog->closeIfNeeded($this->trace);

            $vsetky_predmety = array_merge($vsetky_predmety, $predmety->getData());
        }

        $this->predmety = $vsetky_predmety;
        return $this->predmety;
    }

    public function getFullName()
    {
        $this->loginIfNotAlready();
        return $this->mainScreen->getFullUserName($this->trace);
    }

}