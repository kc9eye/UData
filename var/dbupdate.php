<?php
$update_version = 0.21;

$sql = [
    'ALTER TABLE public.supervisor_comments
    ADD COLUMN subject text'
 ];

$inserts = [
];