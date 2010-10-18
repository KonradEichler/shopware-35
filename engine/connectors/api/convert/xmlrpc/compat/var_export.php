<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Aidan Lister <aidan@php.net>                                |
// +----------------------------------------------------------------------+
//
// $Id: var_export.php,v 1.2 2005/11/21 10:57:23 ggiunta Exp $


/**
 * PHP-XMLRPC "wrapper" functions
 * Generate stubs to transparently access xmlrpc methods as php functions and viceversa
 *
 * @version $Id: xmlrpc_wrappers.inc,v 1.10 2006/09/01 21:49:19 ggiunta Exp $
 * @copyright G. Giunta (C) 2006
 * @author Gaetano Giunta
 * @package XMLRPC
 *
 * @todo separate introspection from code generation for func-2-method wrapping
 * @todo use some better templating system from code generation?
 * @todo implement method wrapping with preservation of php objs in calls
 * @todo when wrapping methods without obj rebuilding, use return_type = 'phpvals' (faster)
 * @todo implement self-parsing of php code for PHP <= 4
 */
if (!function_exists('var_export')) {
    function var_export($array, $return = false, $lvl=0)
    {
        // Common output variables
        $indent      = '  ';
        $doublearrow = ' => ';
        $lineend     = ",\n";
        $stringdelim = '\'';

        // Check the export isn't a simple string / int
        if (is_string($array)) {
            $out = $stringdelim . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $array)) . $stringdelim;
        } elseif (is_int($array) || is_float($array)) {
            $out = (string)$array;
        } elseif (is_bool($array)) {
            $out = $array ? 'true' : 'false';
        } elseif (is_null($array)) {
            $out = 'NULL';
        } elseif (is_resource($array)) {
            $out = 'resource';
        } else {
            // Begin the array export
            // Start the string
            $out = "array (\n";

            // Loop through each value in array
            foreach ($array as $key => $value) {
                // If the key is a string, delimit it
                if (is_string($key)) {
                    $key = str_replace('\'', '\\\'', str_replace('\\', '\\\\', $key));
                    $key = $stringdelim . $key . $stringdelim;
                }

                $val = var_export($value, true, $lvl+1);
                // Delimit value
                /*if (is_array($value)) {
                    // We have an array, so do some recursion
                    // Do some basic recursion while increasing the indent
                    $recur_array = explode($newline, var_export($value, true));
                    $temp_array = array();
                    foreach ($recur_array as $recur_line) {
                        $temp_array[] = $indent . $recur_line;
                    }
                    $recur_array = implode($newline, $temp_array);
                    $value = $newline . $recur_array;
                } elseif (is_null($value)) {
                    $value = 'NULL';
                } else {
                    $value = str_replace($find, $replace, $value);
                    $value = $stringdelim . $value . $stringdelim;
                }*/

                // Piece together the line
                for ($i = 0; $i < $lvl; $i++)
                    $out .= $indent;
                $out .= $key . $doublearrow . $val . $lineend;
            }

            // End our string
            for ($i = 0; $i < $lvl; $i++)
                $out .= $indent;
            $out .= ")";
        }

        // Decide method of output
        if ($return === true) {
            return $out;
        } else {
            echo $out;
            return;
        }
    }
}
?>