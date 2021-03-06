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
 * Represents an interface that delivers a document.
 * 
 * This class is designed for delivering an interface 
 * for a specific document and giving users a method for 
 * doing different things with that document. It is designed to
 * be used with a special class of controller, a document controller.
 * @uses Instance::$pdo
 * @uses Security
 * @uses Mailer
 * @uses Instance::$config
 * @example ../safety/hazcomdoc.php On proper document controller syntax
 * @author Paul W. Lane
 * @package UData\FrameWork\Database\Postgres
 * @license GPLv2
 * @todo Rewrite the controller into a method call from a controller
 * 
 */
class DocumentViewer extends ViewMaker {
    /**
     * @var String APPROVED The document approved state.
     */
    const APPROVED = 'approved';

    /**
     * @var String SEEKING The document seeking approval state
     */
    const SEEKING = 'seeking_approval';

    /**
     * @var String OBSOLETE the document obsolete state
     */
    const OBSOLETE = 'obsolete';

    /**
     * @var String EDITING The document in edition state
     */
    const EDITING = 'in_edition';

    /**
     * @var String EDIT_ACCESS_NAME The security permissions array index name for edit access
     */
    const EDIT_ACCESS_NAME = 'edit';

    /**
     * @var String APPROVE_ACCESS_NAME The security permissions aray index name for approval access
     */
    const APPROVE_ACCESS_NAME = 'approve';

    /**
     * @var PDO $dbh The application PDO object
     */
    protected $dbh;

    /**
     * @var Mailer $mailer The application mailer
     */
    protected $mailer;

    /**
     * @var String $docName The current document name
     */
    protected $docName;

    /**
     * @var Int $rights The current users access rights to the document
     */
    protected $rights;

    /**
     * @var Array $states Array of document ID's representing the documents different states
     */
    protected $states;

    /**
     * @deprecated
     */
    protected $mailperms;

    /**
     * @var String $docURL The URL of the current document interface
     */
    public $docURL;

    /**
     * @var String $approved The ID of the current document in an approved state
     */
    public $approved;

    /**
     * @var String $seeking The ID of the current document in a seeking approval state
     */
    public $seeking;

    /**
     * @var Array $access An indexed array of security permissions for editing the document 
     * and approving the document.
     */
    public $access;

    protected $security;
    protected $config;

    /**
     * Class Constructor
     * @param PDO $dbh The current database access PDO
     * @param Security $security The current security model
     * @param Mailer $mailer The mailer object
     * @param Array $config The current configuration variables
     * @return DocumentViewer
     */
    public function __construct (Instance $server) {
        $this->dbh = $server->pdo;
        $this->mailer = $server->mailer;
        $this->rights = 0;
        $this->security = $server->security;
        $this->config = $server->config;
        parent::__construct($server);
    }

    /**
     * Determines the current users access
     * 
     * Sets the $rights property determined by the current users permission set
     * and the access permissions given in the $access property
     * @return Void
     */
    private function setAccessRights () {
        if (!empty($this->access[self::EDIT_ACCESS_NAME])) {
            foreach($this->access[self::EDIT_ACCESS_NAME] as $perm) {
                if ($this->security->userHasPermission($perm)) {
                    $this->rights = 1;
                }
                elseif ($this->security->userHasRole($perm)) {
                    $this->rights = 1;
                }
            }
        }
        if (!empty($this->access[self::APPROVE_ACCESS_NAME])) {
            foreach($this->access[self::APPROVE_ACCESS_NAME] as $perm) {
                if ($this->security->userHasPermission($perm)) {
                    $this->rights = 2;
                }
                elseif ($this->security->userHasRole($perm)) {
                    $this->rights = 2;
                }
            }
        }
        if ($this->isDocumentOwner()) {
            $this->rights = 2;
        }
    }

    /**
     * Sets the current document for the current interface.
     * 
     * The document given by name to the parameter is used in the 
     * interface
     * @param String $name The document name to dsiplay in the interface.
     * @return Boolean If the document is not empty returns true, otherwise false
     */
    public function setDocument ($name) {
        $this->docName = $name;
        $this->setAccessRights();
        try {
            $sql = 'SELECT id, state, body FROM documents WHERE name = ?';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$name]);
            $this->states = $pntr->fetchAll(PDO::FETCH_ASSOC);
            foreach($this->states as $doc) {
                if ($doc['state'] == self::APPROVED) {
                    $this->approved = $doc['id'];
                }
                elseif ($doc['state'] == self::SEEKING) {
                    $this->seeking = $doc['id'];
                } 
            }
            if (empty($this->states['body'])) {
                return false;
            }
            else {
                return true;
            }
        }
        catch (PDOException $e) {
            throw new Exception($e->message);
        }
    }

    /**
     * Retreives a document form the database by it's ID
     * @param String $id The document iD
     * @return Array The document information
     */
    protected function getDocument ($id) {
        try {
            $sql = 'SELECT * FROM documents WHERE id = ?';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$id]);
            $doc = $pntr->fetch(PDO::FETCH_ASSOC);
            return $doc;
        }
        catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Displays the requested document in an interface
     * 
     * An entire interface is output to the stream that
     * contains the requested document taking into account 
     * the current users access permissions.
     * @param Array $submenu An optional indexed array of links to display in the side navigation
     * @param Array $queryString An optional array of additional query string variables
     * @see ViewMaker::sideDropDownMenu()
     * @return Void
     */
    public function displayDoc($submenu = null,$queryString = null) {
        $this->ViewData['pagetitle'] = $this->docName;
        $document = $this->getDocument($this->approved);
        $edit = '?action=edit';
        $approve = '?action=approve';
        if (!is_null($queryString)) {
            foreach($queryString as $var=>$val) {
                $edit .= "&{$var}={$val}";
                $approve .= "&{$var}={$val}";
            }
        }
        $this->header();
        if (!is_null($submenu)) {
            $this->sideDropDownMenu($submenu);
        }
        echo "<div>\n";
        if ($this->rights >= 1) {
            echo "<a href='{$this->docURL}{$edit}' class='btn btn-info' role='button'>Edit Document</a>";
        }
        if ($this->rights == 2) {
            foreach($this->states as $doc) {
                if ($doc['state'] == self::SEEKING) {
                    echo "&#160;<a href='{$this->docURL}{$approve}' class='btn btn-info' role='button'>See Pending Changes</a>";
                }
            }
        }
        echo "<div class='well'><span class='small'>Approved on: {$document['a_date']}<br />{$document['body']}</div>";
        echo "</div>\n";
        $this->addscrollTopBtn();
        $this->footer();
    }

    /**
     * Outputs an editable version of the selected document inside an interface.
     * 
     * An entire interface with an editable version of the 
     * selected document is output to the stream.
     * @param Array $submenu An optional indexed array of links for the side navigation menu
     * @param Array $queryString An optional array of additional query string variables
     * @see ViewMaker::sideDropDownMenu()
     * @return Void
     */
    public function editDisplay ($submenu = null, $blank_template = '', $queryString = null) {
        if ($this->rights < 1) {return false;}
        $this->ViewData['pagetitle'] = 'Editing:'.$this->docName;
        $document = $this->getDocument($this->approved);
        if (empty($document['body'])) {
            $document['body'] = $blank_template;
        }
        $this->header();
        if (!is_null($submenu)) {
            $this->sideDropDownMenu($submenu);
        }
        echo "<form method='post'>
            <input type='hidden' name='action' value='submit' />
            <input type='hidden' name=':oid' value='{$this->security->secureUserID}' />
            <input type='hidden' name=':name' value='{$this->docName}' />\n";
        if (!is_null($queryString)) {
            foreach($queryString as $var=>$val) {
                echo "<input type='hidden' name='{$var}' value='{$val}' />\n";
            }
        }
        echo "<div class='form-group'><textarea class='form-control' name=':body' required>{$document['body']}</textarea>
            <button type='submit' class='btn btn-danger form-control'>Submit for Approval</button></div>
            </form>";
        $this->footer([
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js',
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/additional-methods.min.js',
            $this->PageData['approot'].'/third-party/tinymce/tinymce.min.js',
            $this->PageData['wwwroot'].'/scripts/docedit.js'
        ]);
        return true;
    }

    /**
     * Outputs a document that is seeking approval encapsulated in an enitire interface
     * 
     * An interface with an editted document that is seeking approval
     * is display. The user has options to approve or disapprove of the 
     * document.
     * @param Array $submenu An optional indexed navigation array
     * @param Array $queryString An optional array of query string parameters in the form `['variable'=>'value',...]`
     * @see ViewMaker::sideDropDownMenu()
     * @return Void
     */
    public function approveDisplay ($submenu = null, $queryString = null) {
        if ($this->rights != 2) {return false;}
        $document = $this->getDocument($this->seeking);
        $this->header();
        if (!is_null($submenu)) {
            $this->sideDropDownMenu($submenu);
        }
        echo "<form method='post'>
                <div class='form-group'>
                    <input type='hidden' name='action' value='submitapproval' />
                    <input type='hidden' name=':name' value='{$this->docName}' />
                    <input type='hidden' name=':aid' value='{$this->security->secureUserID}' />
                    <input type='hidden' name=':id' value='{$this->seeking}' />";
        if (!is_null($queryString)) {
            foreach($queryString as $var=>$val) {
                echo  "input type='hidden' name='{$var}' value='{$val}' />\n";
            }
        }      
        echo "      <textarea name=':body' required>{$document['body']}</textarea>
                    <label for='pass'>Password:
                        <input class='form-contorl' id='pass' type='password' name=':password' placeholder='Required for Approval' required/>
                    </label>                    
                    <button type='submit' class='btn btn-success'>Approve Document</button>&#160;
                    <a href='{$this->docURL}?action=reject&id={$this->seeking}' class='btn btn-danger' role='button'>
                        Reject
                    </a>
                </div>
            </form>";
        $this->footer([
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js',
            'https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/additional-methods.min.js',
            $this->PageData['approot'].'/third-party/tinymce/tinymce.min.js',
            $this->PageData['wwwroot'].'/scripts/docedit.js'
        ]);
        return true;
    }

    /**
     * Submits an edited document to the database seeking approval.
     * 
     * When a document is edited it is submitted for approval through this method.
     * @param Array $data The required data given from the `DocumentViewer::editDisplay()` method.
     * @return Boolean
     * @see DocumentViewer::editDisplay()
     */
    public function submitForApproval ($data) {
        if ($this->rights == 0) return false;
        try {
            $sql = 'INSERT INTO documents (id,name,state,body,oid) VALUES (:id,:name,:state,:body,:oid)';
            $insert = [
                ':id'=>uniqid(),
                ':state'=>self::SEEKING,
                ':name'=>$data[':name'],
                ':body'=>$data[':body'],
                ':oid'=>$data[':oid']
            ];
            $pntr = $this->dbh->prepare($sql);
            $this->dbh->beginTransaction();
            $pntr->execute($insert);
            if (!empty($data['queryString'])) $insert['queryString'] = $data['queryString'];
            $this->emailReview($insert);
            $this->dbh->commit();
            return true;
        }
        catch(PDOException $e) {
            trigger_error($e->message,E_USER_WARNING);
            $this->dbh->rollback();
            return false;
        }
        catch (Exception $e) {
            trigger_error($e->message,E_USER_WARNING);
            $this->dbh->rollback();
            return false;
        }
    }

    /**
     * Sends email to approvers that a document is seeking approval
     * 
     * This method sends an email to users with permissions to approve the 
     * document that has been submitted.
     * @param Array $data Data submitted to DocumentViewer::submitForApproval()
     * @return Boolean
     * @see DocumentViewer::submitForApproval()
     */
    public function emailReview ($data) {
        try {
            $url = '?action=approve&id='.$data[':id'];
            if (!empty($data['queryString'])) $url .= '&'.$data['queryString'];
            $body = $this->mailer->wrapInTemplate(
                "docreview.html",
                "<a href='{$this->docURL}{$url}}'><strong>{$data[':name']}</strong></a>"
            );
            foreach($this->access[self::APPROVE_ACCESS_NAME] as $perm) {
                foreach($this->security->getUsersByPerm($perm) as $reviewer) {
                    $this->mailer->sendMail(['to'=>$reviewer['username'],'subject'=>'Pending Document Change','body'=>$body]);
                }
            }
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->message,E_USER_WARNING);
            return false;
        }
    }

    /**
     * Approves a document edit and marks the document approved
     * 
     * Used by the `DocumentViewer::approveDisplay()` to turn an
     * edited document into an approved document.
     * @param Array $data Data submitted by the `DocumentViewer::approveDisplay()`
     * @return Boolean
     * @see DocumentViewer::approveDisplay()
     */
    public function approvalGranted ($data) {
        if ($this->rights != 2) {return false;}
        try {
            $sql = 'SELECT password FROM user_accts WHERE id = ?';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$data[':aid']]);
            $pass = $pntr->fetch(PDO::FETCH_ASSOC);
            $this->dbh->beginTransaction();
            if (password_verify($data[':password'],$pass['password'])) {
                $sql = 'UPDATE documents SET state = :state WHERE id = :id';
                $pntr = $this->dbh->prepare($sql);
                $pntr->execute([':state'=>self::OBSOLETE,':id'=>$this->approved]);

                $sql = 'UPDATE documents SET state = :state, aid = :aid, a_date = now() WHERE id = :id';
                $pntr = $this->dbh->prepare($sql);
                $pntr->execute([':state'=>self::APPROVED,':aid'=>$data[':aid'],':id'=>$data[':id']]);
                $this->dbh->commit();
                return true;
            }
            else {
                return false;
            }
        }
        catch (Exception $e) {
            trigger_error($e->message, E_USER_WARNING);
            return false;
        }
    }

    /**
     * Database rollback method for transactions
     * 
     * A public rollback method for database transaction that fail
     * either in this class or the calling controller.
     * @param String $id The document ID to rollback
     * @return Boolean
     */
    public function rollBack ($id) {
        if ($this->rights != 2) {return false;}
        try {
            $sql = 'DELETE FROM documents WHERE id = ?';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([$id]);
            return true;
        }
        catch (Exception $e) {
            trigger_error($e->message,E_USER_WARNING);
            return false;
        }
    }

    /**
     * Determines whether the current user is the current document owner.
     * @return Boolean
     * @uses Security
     */
    private function isDocumentOwner () {
        try {
            $sql = 'SELECT oid FROM documents WHERE name = :name AND state = :state';
            $pntr = $this->dbh->prepare($sql);
            $pntr->execute([':name'=>$this->docName,':state'=>self::APPROVED]);
            $oid = $pntr->fetch(PDO::FETCH_ASSOC)['oid'];
            if ($this->security->secureUserID == $oid) {
                return true;
            }
        }
        catch (PDOException $e) {
            return false;
        }
        return false;
    }
}