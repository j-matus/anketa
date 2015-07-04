<?php

namespace AnketaBundle\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use AnketaBundle\Entity\User;

class AnketaTwigExtension extends \Twig_Extension {
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getGlobals() {
        return array(
            'menu' => array(
                'hlasovanie' => $this->container->get('anketa.menu.hlasovanie'),
                'statistics' => $this->container->get('anketa.menu.statistics'),
            ),
            'access' => array(
                'hlasovanie' => $this->container->get('anketa.access.hlasovanie'),
                'statistics' => $this->container->get('anketa.access.statistics'),
            ),
        );
    }

    public function getFunctions()
    {
        return array(
            'analytics' => new \Twig_Function_Method($this, 'getAnalytics', array('is_safe' => array('html'))),
            'user_bar' => new \Twig_Function_Method($this, 'getUserBar', array('is_safe' => array('html'))),
            'transtext' => new \Twig_Function_Method($this, 'transText'),
            'trans' => new \Twig_Function_Method($this, 'transHtml', array('is_safe' => array('html'))),
        );
    }

    public function getAnalytics() {
        $parameter = 'google_analytics_tracking_code';
        if ($this->container->hasParameter($parameter)) {
            $ga = $this->container->getParameter($parameter);
        }
        else {
            $ga = null;
        }
        return $this->container->get('templating')->render('AnketaBundle::analytics.html.twig',
                        array('ga_tracking_code' => $ga));
    }

    public function getUserBar() {
        $user = null;
        $token = $this->container->get('security.context')->getToken();
        if ($token !== null) {
            $user = $token->getUser();
        }
        return $this->container->get('templating')->render('AnketaBundle::user_bar.html.twig',
                             array('user' => ($user instanceof User ? $user : null)));
    }

    private function transBase($id, $number) {
        if ($number === NULL) {
            $message = $this->container->get('translator')->trans($id);
        } else {
            $message = $this->container->get('translator')->transChoice($id, $number);
        }
        if ($id === $message) {
            throw new \Exception('Missing translation: ' . $id);
        }
        return $message;
    }

    /**
     * Translates a string. The translation is assumed to be normal text,
     * and the output is also normal text (Twig will escape it).
     * "%foo%" parameters will be kept (they should also contain normal text),
     * while "<!foo!>" parameters (containing raw HTML) are forbidden.
     */
    public function transText($id, $parameters = array(), $number = NULL) {
        $message = $this->transBase($id, $number);
        $escaped = array();
        foreach ($parameters as $key => $value) {
            if (substr($key, 0, 2) == '<!' && substr($key, -2) == '!>') {
                throw new \Exception('Used HTML escape in transtext function.');
            } else if (substr($key, 0, 1) == '%' && substr($key, -1) == '%') {
                $escaped[$key] = $value;
            } else {
                throw new \Exception('Malformed transtext parameter: ' . $key);
            }
        }
        return strtr($message, $escaped);
    }

    /**
     * Translates a string. The translation is assumed to be HTML code.
     * "%foo%" parameters will be escaped, while "<!foo!>" parameters can
     * contain raw HTML and will be passed through.
     */
    public function transHtml($id, $parameters = array(), $number = NULL) {
        $message = $this->transBase($id, $number);
        $escaped = array();
        foreach ($parameters as $key => $value) {
            if (substr($key, 0, 2) == '<!' && substr($key, -2) == '!>') {
                $escaped[$key] = $value;
            } else if (substr($key, 0, 1) == '%' && substr($key, -1) == '%') {
                $escaped[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } else {
                throw new \Exception('Malformed trans parameter: ' . $key);
            }
        }
        return strtr($message, $escaped);
    }

    public function getName()
    {
        return 'anketa';
    }

}
