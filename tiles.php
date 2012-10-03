<?php
$src = 'src.jpg';
list($imageWidth, $imageHeight) = getimagesize($src);

echo "Image size: w = $imageWidth, h = $imageHeight \n";

$maxLength = max($imageHeight, $imageWidth);

$levels = (int)ceil(log($maxLength) / log(2)) - 7;
echo "Levels: $levels\n";
if ($levels > 10 || $levels < 1) {
	echo "Levels must be between 1 and 10\n";
	exit(1);
}

$w = $wMax = $h = (int)pow(2, $levels + 7);

echo "h = $h, w = $w\nAdding borders\n";

$level = $levels - 1;
$filename = sprintf("images/image%d.jpg", $level);
$image = imagecreatefromjpeg($src);
$outImage = imagecreatetruecolor($w, $h);
if(!$status = imagecopyresampled($outImage, $image, (int)ceil($w - $imageWidth + 1) / 2, (int)ceil($h - $imageHeight + 1) / 2, 0, 0, $imageWidth, $imageHeight, $imageWidth, $imageHeight))
	exit($status);

imagejpeg($outImage, $filename, 100);
imagedestroy($image);
for ($level = $levels - 1; $level >= 0;) {
	echo "tiling level " . $level . "\n h = $h, w = $w";
	$filename = sprintf("images/image%d.v", $level);

	$max = (int)pow(2, $level);
	$n = 0;
	$y = 0;
	for ($j = 0; $j < $max; $j++) {
		$x = 0;
		for ($i = 0; $i < $max; $i++) {
			echo ".";

			$name = sprintf("images/tile-%d-%d-%d.jpg", $level, $j, $i);

			$tileImage = imagecreatetruecolor(256, 256);
			if(!$status = imagecopyresampled($tileImage, (isset($levelImage)) ? $levelImage : $outImage, 0, 0, $x, $y, 256, 256, 256, 256))
				exit($status);
			imagejpeg($tileImage,$name,100);
			imagedestroy($tileImage);
			$n++;
			$x += 256;
		}
		$y += 256;
	}
	$level--;
	if ($level >= 0) {
		$w = $h = $h / 2;
		echo "\nshrinking for level $level, h = $h, w = $w\n";

		$filename = sprintf("images/image%d.jpg", $level);
		$levelImage = imagecreatetruecolor($w, $h);
		var_dump($levelImage, $outImage, 0, 0, 0, 0, $w, $h, $wMax, $wMax);
		if(!$status = imagecopyresampled($levelImage, $outImage, 0, 0, 0, 0, $w, $h, $wMax, $wMax))
			exit($status);
		imagejpeg($levelImage,$filename,100);
	}
}