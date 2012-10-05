<?php
include "TilerImagick.php";

$tiler = new TilerImagick('src.jpg','images/',true);
$tiler->process();