<?php
$update_version = 0.20;

$sql = [
    'CREATE TABLE public.shipping
    (
        id character varying NOT NULL,
        name character varying NOT NULL,
        uid character varying NOT NULL,
        notify boolean NOT NULL DEFAULT false,
        _date timestamp with time zone NOT NULL DEFAULT now(),
        PRIMARY KEY (id)
    );',
    'CREATE TABLE public.shipment
    (
        id character varying NOT NULL,
        shipid character varying NOT NULL,
        carrier character varying NOT NULL,
        vehicle_number character varying NOT NULL,
        comments text,
        ready boolean NOT NULL DEFAULT False,
        uid character varying NOT NULL,
        _date timestamp with time zone DEFAULT now(),
        PRIMARY KEY (id)
    );',
    'CREATE TABLE public.shipping_log
    (
        id character varying NOT NULL,
        item character varying NOT NULL,
        shipmentid character varying NOT NULL,
        uid character varying NOT NULL,
        _date timestamp with time zone NOT NULL DEFAULT now(),
        PRIMARY KEY (id)
    );'
 ];

$inserts = [
    'INSERT INTO perms VALUES (:id,:name)'=>[
        [':id'=>uniqid(),':name'=>"createShippingCategory"],
        [':id'=>uniqid(),':name'=>"editShippingLog"],
        [':id'=>uniqid(),':name'=>"shipEquipment"]
    ],
    'INSERT INTO notifications VALUES (:id,:descr,:type,now(),:uid)'=>[
        [':id'=>uniqid(),':descr'=>"Shipment Ready",':type'=>"EMAIL",':uid'=>'5bc4c8a517238'],
        [':id'=>uniqid(),':descr'=>"Shipping Comment",':type'=>"EMAIL",':uid'=>'5bc4b8a517238']
    ]
];