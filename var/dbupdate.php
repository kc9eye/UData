<?php
$update_version = 0.18;

$sql = [
    "ALTER TABLE public.cell_material
    ADD COLUMN label character varying;"
];

$inserts = [

];