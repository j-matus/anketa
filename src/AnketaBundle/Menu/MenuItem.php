<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Controller
 * @author     Peter Perešíni <ppershing@gmail.com>
 * @author     Martin Králik <majak47@gmail.com>
 */

namespace AnketaBundle\Menu;

use AnketaBundle\Lib\Preconditions;

// TODO(ppershing): make things private
class MenuItem {
    public $title;
    public $href;
    public $children;
    public $expanded;
    public $active;
    public $lecturer;
    public $trainer;
    /**
     * show only expanded child.
     * Useful when the sub-menu list is extremely long
     */
    public $only_expanded;

    /*
     * @var MenuItemProgressbar Cointains information about completion progress
     *                          for this menu item.
     */
    private $progressbar;

    public function __construct($title, $link) {
        Preconditions::checkIsString($title);
        Preconditions::checkIsString($link);
        $this->title = $title;
        $this->href = $link;
        $this->children = array();
        $this->expanded = false;
        $this->active = false;
        $this->lecturer = false;
        $this->trainer = false;
        $this->only_expanded = false;
        $this->progressbar = new MenuItemProgressbar($this);
    }

    public function getProgressbar() {
        return $this->progressbar;
    }

}

