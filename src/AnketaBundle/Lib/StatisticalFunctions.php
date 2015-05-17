<?php
/**
 * This file contains functions computing some statistics over data
 *
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Lib
 * @author     Peter Perešíni <ppershing@gmail.com>
 */
namespace AnketaBundle\Lib;

/**
 * Compute statistics over data
 */
class StatisticalFunctions {

    /**
     * Checks validity and normalize data
     *
     * @param array $data array whose items may be
     *   $value or array($value, $count)
     *
     * Warning: we do not "group" the same values, so for $data = array(1, 1, 1)
     * the output is array(3 x array(1, 1))
     *
     * @return array(array(value,count))
     */
    private static function checkAndNormalizeData(array $data) {
        $result = array();
        foreach ($data as $item) {
            if (is_array($item)) {
                Preconditions::check(array_keys($item) == array(0, 1));
                Preconditions::check(is_numeric($item[0]));
                Preconditions::check(is_int($item[1]));
                Preconditions::check($item[1] >= 0);
                if ($item[1] > 0) {
                    $result[] = $item;
                }
            } else {
                Preconditions::check(is_numeric($item));
                $result[] = array($item, 1);
            }
        }
        return $result;
    }

    /**
     * Count number of data points
     *
     * @param array $data @see checkAndNormalizeData for details
     *
     * @return int count
     */
    public static function cnt(array $data) {
        $data = self::checkAndNormalizeData($data);
        return array_sum(array_map(function ($x) {return $x[1];}, $data));
    }

    /**
     * Return average value of data points
     *
     * Average value is defined as sum/cnt
     *
     * @param array $data @see checkAndNormalizeData for details
     *
     * @return double average value
     * @throws
     */
    public static function average(array $data) {
        $data = self::checkAndNormalizeData($data);
        $cnt = self::cnt($data);
        if ($cnt == 0) {
            throw new StatisticalException("No data to average.");
        }
        return array_sum(array_map(function ($x) {return $x[0] * $x[1];}, $data)) / $cnt;
    }

    static function compareDataByValue($a, $b) {
        if ($a[0] == $b[0]) return 0;
        return ($a[0] < $b[0]) ? -1 : 1;
    }

    /**
     * Return median of data points
     *
     * Median is the middle data point in case of odd number of data points
     * and average of the two middle data points in case of even number of data
     * points.
     *
     * @param array $data @see checkAndNormalizeData for details
     *
     * @return double median value
     * @throws
     */
    public static function median(array $data) {
        $data = self::checkAndNormalizeData($data);
        $cnt = self::cnt($data);
        if ($cnt == 0) {
            throw new StatisticalException("No data to find median.");
        }
        usort($data, array('AnketaBundle\Lib\StatisticalFunctions', 'compareDataByValue'));
        $bucket = 0;
        $remaining = intval(($cnt - 1) / 2);
        foreach ($data as $val) {
            if ($remaining - $val[1] < 0) {
                break;
            }
            $remaining -= $val[1];
            $bucket++;
        }
        if ($cnt % 2 == 0 && $remaining == ($data[$bucket][1] - 1) ) {
            return ($data[$bucket][0] + $data[$bucket+1][0])/2;
        }
        return $data[$bucket][0];
    }

    /**
     * Returns *sample* standard deviation
     *
     * Sample standard deviation is defined as
     * sqrt(1/(N-1) sum (x - avg)^2)
     *
     * @return *sample* standard deviation
     */
    public static function stddev(array $data) {
        $data = self::checkAndNormalizeData($data);
        $cnt = self::cnt($data);
        if ($cnt < 2) {
            throw new StatisticalException("Not enough data for stddev");
        }
        $avg = self::average($data);

        $sigmaFunct = function ($x) use ($avg) {
                return $x[1] * ($avg - $x[0]) * ($avg - $x[0]);
            };
        return sqrt(array_sum(array_map($sigmaFunct, $data)) / ($cnt - 1));
    }
}
