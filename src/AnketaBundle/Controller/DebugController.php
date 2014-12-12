<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Controller
 * @author     Martin Sucha <anty.sk+svt@gmail.com>
 */

namespace AnketaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Controller for testing (rarely used) services
 */
class DebugController extends Controller {

    public function indexAction() {
        return $this->render('AnketaBundle:Debug:index.html.twig');
    }

    public function aisAction() {
        $retriever = $this->get('anketa.ais_retriever');
        $semestre = $this->getRequest()->query->get('semestre');
        $semestreArr = null;
        if ($semestre != null) {
            $semestreArr = array();
            foreach (explode(';', $semestre) as $semester) {
                $sem = explode(':', $semester, 2);
                if (count($sem) == 2) {
                    $semestreArr[] = $sem;
                }
            }
        }
        $result = $retriever->getResult($semestreArr);
        $predmety = $result['subjects'];
        return $this->render('AnketaBundle:Debug:ais.html.twig',
                array('predmety' => $predmety));
    }

}
