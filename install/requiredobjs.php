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
 * New databases are initialized from this template.
 * @todo 'Finish this initial template
 */
$update_version = 0.1;

 $sql = [
     'CREATE TABLE auth_tokens (
        id varchar primary key,
        _date timestamp with time zone default NOW(),
        selector varchar not null,
        validator varchar not null,
        uid varchar not null)',
    
    'CREATE TABLE perms (
        id varchar primary key,
        name varchar not null)',
    
    'CREATE TABLE profiles (
        id varchar primary key,
        first varchar,
        middle varchar,
        last varchar,
        other varchar,
        uid varchar,
        address varchar,
        address_other,
        city varchar,
        state_prov varchar,
        postal_code varchar,
        home_phone varchar,
        cell_phone varchar,
        alt_phone varchar,
        email varchar,
        alt_email varchar,
        e_contact_name varchar,
        e_contact_number varchar,
        e_contact_relation varchar)',

    'CREATE TABLE roles (
        id varchar primary key,
        name varchar not null)',

    'CREATE TABLE role_perms (
        pid varchar references perms(id),
        rid varchar references roles(id))',
    
    'CREATE TABLE user_accts (
        id varchar primary key,
        username varchar not null,
        password varchar not null,
        firstname varchar not null,
        lastname varchar,
        alt_email varchar,
        _date timestamp with time zone default NOW(),
        verfiy_code varchar)',
    
    'CREATE TABLE user_accts_holding (
        id varchar primary key,
        username varchar not null,
        password varchar not null,
        firstname varchar not null,
        lastname varchar,
        alt_email varchar,
        _date timestamp with time zone default NOW(),
        verify_code varchar not null)',
    
    'CREATE TABLE user_roles (
        rid varchar references roles(id),
        uid varchar references user_accts(id))',

    'CREATE TABLE sds (
        id varchar primary key,
        file varchar not null,
        name varchar not null,
        used varchar not null,
        dist varchar not null,
        _date timestamp with time zone default NOW(),
        added_by varchar,
        meta varchar,
        search tsvector);
    CREATE INDEX search_index ON sds USING GIN (search);
    CREATE TRIGGER ts_update BEFORE INSERT OR UPDATE ON sds FOR EACH ROW EXECUTE PROCEDURE
    tsvector_update_trigger(search,\'pg_catalog.english\',name,dist,meta)',

    'CREATE TABLE documents (
        id varchar primary key,
        state varchar default \'edit\',
        _date timestamp with time zone default NOW(),
        body varchar default \'Click Here To Edit\',
        oid varchar not null,
        name varchar default \'Unknown Document\',
        aid varchar,
        a_date timestamp with time zone,
        search tsvector);
        CREATE INDEX docs_index on documents USING GIN (search_index);
        CREATE TRIGGER docs_update BEFORE INSERT OR UPDATE ON documents FOR EACH ROW EXECUTE PROCEDURE
        tsvector_update_trigger(search,\'pg_catalog.english\',name,body)' #....
 ];

 $inserts = [
     'insert into perms values (:id,:name)' => [
         [':id'=>uniqid(),':name'=>'adminAll']#....
     ]
];