<?php
// Copyright (c) 2010 The Fajr authors (see AUTHORS).
// Use of this source code is governed by a MIT license that can be
// found in the LICENSE file in the Fajr project root directory.

/**
 * Contains wrapper for usual argument-checking in function calls.
 *
 * @package    Libfajr
 * @subpackage Base
 * @author     Peter Perešíni <ppershing+fajr@gmail.com>
 * @filesource
 */
namespace AnketaBundle\Lib;
use InvalidArgumentException;

/**
 * Provides easy way to do common argument-checking
 * for function calls.
 *
 * Note: Use only for argument checking!
 *
 * @package    Libfajr
 * @subpackage Base
 * @author     Peter Perešíni <ppershing+fajr@gmail.com>
 */
class Preconditions
{
  /**
   * Checks that $variable is not null
   *
   * @param mixed  $variable variable to check
   * @param string $name variable name to display in error
   *
   * @returns void
   * @throws InvalidArgumentException
   */
  public static function checkNotNull($variable, $message = null)
  {
    if ($variable === null) {
      throw new InvalidArgumentException($message);
    }
  }

  /**
   * Checks that $variable is a string
   *
   * @param mixed  $variable variable to check
   * @param string $message message to display in error
   *
   * @returns void
   * @throws InvalidArgumentException
   */
  public static function checkIsString($variable, $message = null)
  {
    if (!is_string($variable)) {
      throw new InvalidArgumentException($message);
    }
  }

  /**
   * Checks that $variable is a number (integer, float or double)
   *
   * @param mixed  $variable variable to check
   * @param string $message message to display
   *
   * @returns void
   * @throws InvalidArgumentException
   */
  public static function checkIsNumber($variable, $message = null)
  {
    if (!(is_int($variable) || is_float($variable) || is_double($variable))) {
      throw new InvalidArgumentException($message);
    }
  }

  /**
   * Checks that $variable contain an integer.
   * Note that we DO NOT CHECK type of variable but content.
   * Note: '' empty string is not valid
   * Note: boolean values are not valid too
   *
   * @param mixed  $variable variable to check
   * @param string $message message to throw
   *
   * @returns void
   * @throws InvalidArgumentException
   */
  public static function checkContainsInteger($variable, $message = null)
  {
    if (is_object($variable) ||
        is_bool($variable) ||
        (string)(int)$variable != $variable) {
      throw new InvalidArgumentException($message);
    }
  }

  /**
   * Checks that $variable is a string and matches a given PCRE
   *
   * @param string $pattern PCRE pattern to check against
   * @param mixed  $variable variable to check
   * @param string $name variable name to display in error
   *
   * @returns void
   * @throws InvalidArgumentException
   */
  public static function checkMatchesPattern($pattern, $variable, $message = null)
  {
    self::checkIsString($variable, $message);
    if (!preg_match($pattern, $variable)) {
      throw new InvalidArgumentException($message);
    }
  }

  /**
   * Checks that $expression is true
   *
   * @param bool   $expression boolean result of an expression
   * @param string $message error message
   *
   * @returns void
   * @throws InvalidArgumentException
   */
  public static function check($expression, $message = null)
  {
    assert(is_bool($expression));
    if (!$expression) {
      throw new InvalidArgumentException($message);
    }
  }

}
