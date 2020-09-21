<?php
/* This file is part of UData.
 * Copyright (C) 2018 Paul W. Lane <kc9eye@outlook.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
/**
 * SearchStringFormater Class Framework
 * 
 * @package UData\Framework\Database\Postgres
 * @link https://www.postgresql.org/docs/9.6/textsearch-tables.html#TEXTSEARCH-TABLES-SEARCH
 * @author Paul W. Lane
 * @license GPLv2
 */
class SearchStringFormater {

    public $formatedString;

    /**
     * Returns either the object or the formated search string
     * @param String $string Optional string to format for search
     * @return Mixed If given string returns the formated search string, otherwise 
     * returns the SearchStringFormater object
     */
    public function __construct ($string = null) {
        if (!is_null($string)) 
            $this->formatedString = $this->formatSearchString($string);
    }

    /**
     * Formats a given string for use in a database search query
     * @param String $string The string to format
     * @return String A database search formatted string
     */
    public function formatSearchString ($string) {
        $string = trim($string);
        $pattern[0] = '/("|\')/i';
        $pattern[1] = '/\b (AND|&) \b/';
        $pattern[2] = '/\b (OR|\|) \b/';
        $pattern[3] = '/\b( +)\b/i';
        $pattern[4] = '/^(.*)$/i';
        $replacment[0] = ' ';
        $replacment[1] = ' & ';
        $replacment[2] = ' | ';
        $replacment[3] = ' | ';
        $replacment[4] = '$1';
        $this->formatedString = preg_filter($pattern,$replacment,$string);
        return $this->formatedString;
    }

}