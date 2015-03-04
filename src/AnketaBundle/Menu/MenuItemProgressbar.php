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

class MenuItemProgressbar {

    /**
     *
     * @var MenuItem object which uses this progressbar
     */
    private $menu_item;

    /**
     * @var numeric progress so far for this item
     *
     * Note: do not consider children status unless includeChildren is false
     */
    private $progress_done;

    /**
     * @var numeric maximum possible progress for this item
     *
     * Note: do not consider children status unless includeChildren is false
     */
    private $progress_all;

    /**
     * @var boolean change including children progress into object's progress
     */
    private $includeChildren;

    public function __construct($menu_item, $progress_all = 0, $progress_done = 0) {
        $this->menu_item = $menu_item;
        $this->progress_done = $progress_done;
        $this->progress_all = $progress_all;
        $this->includeChildren = true;
    }

    public function getIncludeChildren() {
        return $this->includeChildren;
    }

    public function setIncludeChildren($value) {
        $this->includeChildren = $value;
    }

    /**
     * Sets the progress of the current item.
     * Note that this function sets only item's own progress,
     * the resulting progress is calculated
     * also based on children!
     * (If $includeChildren is true.)
     */
    public function setProgress($done, $total) {
        Preconditions::checkIsNumber($done);
        Preconditions::checkIsNumber($total);
        Preconditions::check($done >= 0);
        Preconditions::check($total >= 0);
        $this->progress_done = $done;
        $this->progress_all = $total;
    }

    public function getProgressDone() {
        $done = $this->progress_done;
        if ($this->includeChildren && $this->menu_item) foreach ($this->menu_item->children as $item) {
            $done += $item->getProgressbar()->getProgressDone();
        }
        return $done;
    }

    public function getProgressTotal() {
        $total = $this->progress_all;
        if ($this->includeChildren && $this->menu_item) foreach ($this->menu_item->children as $item) {
            $total += $item->getProgressbar()->getProgressTotal();
        }
        return $total;
    }

    public function getPercentDone() {
        $done = $this->getProgressDone();
        $total = $this->getProgressTotal();
        if ($total == 0) {
            return null;
        } else {
            return 100 * $done / $total;
        }
    }
}

