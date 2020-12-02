<?php
$update_version = 0.216;

$sql = [
    'CREATE TABLE public.ppe
    (
        id character varying NOT NULL,
        description character varying NOT NULL,
        vendor character varying NOT NULL,
        unit_price money NOT NULL,
        uid character varying NOT NULL,
        gen_date timestamp with time zone NOT NULL DEFAULT now(),
        PRIMARY KEY (id)
    )',
    'CREATE TABLE public.ppe_usage
    (
        id character varying NOT NULL,
        eid character varying NOT NULL,
        ppeid character varying NOT NULL,
        returned boolean NOT NULL DEFAULT false,
        uid character varying NOT NULL,
        gen_date timestamp with time zone NOT NULL DEFAULT now(),
        PRIMARY KEY (id)
    )',
    'ALTER TABLE public.ppe_usage
        ADD COLUMN cost money NOT NULL DEFAULT 1.0',
    "INSERT into perms values ('5fc63e7e9625d','readPPE')",
    "INSERT into perms values ('5fc63f11b62e9','writePPE')",
    "INSERT into perms values ('5fc63f329de61','executePPE')"
 ];

$inserts = [

];