<?php
$update_version = 0.19;

$sql = [
    'CREATE TABLE public.cell_prints
    (
        id character varying NOT NULL,
        cellid character varying NOT NULL,
        "number" character varying NOT NULL,
        uid character varying NOT NULL,
        _date timestamp with time zone DEFAULT now(),
        PRIMARY KEY (id)
    )',
    'CREATE TABLE public.cell_files
    (
        id character varying NOT NULL,
        fid character varying NOT NULL,
        cellid character varying NOT NULL,
        uid character varying NOT NULL,
        _date timestamp with time zone DEFAULT now(),
        PRIMARY KEY (id)
    )'
 ];

$inserts = [
];