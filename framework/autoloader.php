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
 * The class autoloader
 * 
 * All class files should be named after the class that resides in them in lowercase and ending
 * in one of the extensions given by `spl_autoload_extension()` or given to the constructor
 * in the `$class_ext` array.
 * @package UData\Framework
 * @author Paul W. Lane
 * @license GPLv2
 */
Class Autoloader {
    /**
     * Holds the $initial_path 
     * @var String
     */
    private $dir;

    /**
     * Holds the $class_ext array
     * @var Array
     */
    private $extns;

    /**
     * Class constructor
     * 
     * Initializes the loader and sets all class paths and class file extension
     * from the parameters given.
     * 
     * @param String $initial_path The string filepath of a directory to recursive look for
     * class files. All directories under the one given are searched for class files.
     * 
     * @param Array $class_ext Optional unindexed array of string to search for as class
     * file extensions. Defaults to `spl_autoload_extensions()`.
     * 
     * @author Paul W. Lane
     */
     public function __construct ($initial_path, $class_ext = []) {
        $this->SetIncludePath($initial_path);
        $this->SetLoadableExtensions($class_ext);
        spl_autoload_register([$this,'load']);
    }

    /**
     * Loads the class files on the fly
     * 
     * Loads class files using spl_autoload().
     * @param String $obj The class name to load
     * @author Paul W. Lane
     */
    private function load ($obj) {
        spl_autoload(strtolower($obj));
    }

    /**
     * Arms the PHP include path to the recursed `$initial_path` parameter
     * 
     * @param String $base Base directory to iterate
     */
    private function SetIncludePath ($base) {
        foreach(new DirectoryIterator($base) as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isDir() && $fileinfo->getFilename() != '.git') {
                set_include_path(get_include_path().PATH_SEPARATOR.$fileinfo->getPathname());
                $this->SetIncludePath($fileinfo->getPathname());
            }
        }
    }

    /**
     * Arms the PHP extension to look for loadable classes
     * 
     * @param Array $exts Extension to look for.
     */
    private function SetLoadableExtensions ($exts) {
        foreach($exts as $ext) {
            spl_autoload_extensions(spl_autoload_extensions().','.$ext);
        }
    }

}