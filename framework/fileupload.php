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
 * Handles file uploads in a sane manner
 * 
 * This handles file uploads in a sane manner for 
 * the FileIndexer class.
 * 
 * @author Paul W. Lane
 * @uses FileIndexer
 * @package UData\Framework
 */
class FileUpload {

    const MAX_UPLOAD_SIZE = 	3145728; //3MB for Aimee

    /**
     * @var Boolean $multiple Whether the file upload contains multiple files
     */
    public $multiple;

    /**
     * @var Array $files A sane array of the uploaded file information in a 
     * standard format for the FileIndexer
     */
    public $files;

    /**
     * Class construtor
     * 
     * @param String $input_name The name of the file form entry. This should 
     * most times be FileIndexer::UPLOAD_NAME. 
     * @return FileUpload
     * @see FileIndexer::UPLOAD_NAME
     */
    public function __construct ($input_name) {
        $this->files = [];
        if (!empty($_FILES)) {
            if (is_array($_FILES[$input_name]['error'])) {
                $this->multiple = true;
                $this->multipleFiles($input_name);
            }
            else {
                $this->multiple = false;
                $this->handleFile($input_name);
            }
            return $this;
        }
        else return false;
    }

    /**
     * Handles multiple file entry uploads
     * 
     * @param String $input_name The form file entry name.
     * @return Void
     */
    protected function multipleFiles($input_name) {
        $files = $_FILES[$input_name];
        foreach($files['error'] as $key => $value) {
            if ($value != \UPLOAD_ERR_OK) {
                throw new UploadException($value);
            }
            $push = [
                'name'=> $files['name'][$key],
                'tmp_name'=>$files['tmp_name'][$key],
                'type'=>$files['type'][$key],
                'size'=>$files['size'][$key],
                'error'=>$value
            ];
            array_push($this->files, $push);
        }
    }

    /**
     * Handles a single file entry upload
     * @param String $input_name The form file entry name
     * @return Void
     */
    protected function handleFile($input_name) {
        $files = $_FILES[$input_name];
        if ($files['error'] != \UPLOAD_ERR_OK) {
            throw new UploadException($files['error']);
        }
        if ($files['size'] > self::MAX_UPLOAD_SIZE) throw new UploadException(UPLOAD_ERR_INI_SIZE);
        $push = [
            'name'=>$files['name'],
            'tmp_name'=>$files['tmp_name'],
            'type'=>$files['type'],
            'size'=>$files['size'],
            'error'=>$files['error']
        ];
        array_push($this->files,$push);
    }
}

/**
 * Extends the Exception class for use by the FileUpload class
 * 
 * @return UploadException
 * @author Paul W. Lane
 */
class UploadException extends Exception {

    public function __construct ($code) {
        $message = $this->codeToMessage($code);
        parent::__construct($message, $code);
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize config directive";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE form directive";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }
}