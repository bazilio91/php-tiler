<?php
include "Tiler.php";

$tiler = new Tiler('src.jpg','images/',true);
$tiler->process();