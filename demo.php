<?php

include('mosaic.php');

$Mosaic = new Mosaic("input.jpg");
$Mosaic->shape_margin = 3; //optional
$Mosaic->shape_size = 20; //optional
$Mosaic->create_mosaic("smoothcircle");