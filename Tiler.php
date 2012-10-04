<?php
/**
 * Image tiler class for custom Google maps or others
 * @property int $levels Levels count
 * @link https://github.com/bazilio91/php-tiler
 *
 * Dual licensed under the MIT and GPL licenses:
 * @link http://www.opensource.org/licenses/mit-license.php
 * @link http://www.gnu.org/licenses/gpl.html
 *
 */
class Tiler
{
	private $srcWidth;
	private $srcHeight;
	private $srcFilename;
	private $dstFolder;
	private $logEnabled = false;
	public $levels;


	/**
	 * @param string $srcFilename Source image
	 * @param string $dstFolder Destination folder for tiles and shrinks
	 * @param bool $log Enable log
	 * @throws TilerException
	 */
	function __construct($srcFilename, $dstFolder, $log = false)
	{
		if (!is_file($srcFilename))
			throw new TilerException("Input file not found", 1);
		$this->srcFilename = $srcFilename;

		if (!is_dir($dstFolder))
			throw new TilerException("Destination folder not found", 1);
		$this->dstFolder = $dstFolder;

		$this->logEnabled = $log;
		list($this->srcWidth, $this->srcHeight) = getimagesize($this->srcFilename);
		$this->log("Image size: w = $this->srcWidth, h = $this->srcHeight");

		$maxLength = max($this->srcHeight, $this->srcWidth);

		$this->levels = (int)ceil(log($maxLength) / log(2)) - 7;
		$this->log("Levels: $this->levels");
		if ($this->levels > 10 || $this->levels < 1) {
			throw new TilerException("Levels must be between 1 and 10");
		}
	}

	public function process()
	{
		$w = $wMax = $h = (int)pow(2, $this->levels + 7);

		$this->log("Adding borders with new h = $h, w = $w");

		$level = $this->levels - 1;
		$filename = sprintf("images/image%d.jpg", $level);
		$image = imagecreatefromjpeg($this->srcFilename);
		$outImage = imagecreatetruecolor($w, $h);
		if (!$status = imagecopyresampled($outImage, $image, (int)ceil($w - $this->srcWidth + 1) / 2, (int)ceil($h - $this->srcHeight + 1) / 2, 0, 0, $this->srcWidth, $this->srcHeight, $this->srcWidth, $this->srcHeight))
			throw new TilerException("Error adding borders", $status);

		imagejpeg($outImage, $filename, 100);
		imagedestroy($image);
		for ($level = $this->levels - 1; $level >= 0;) {
			$this->log("Tiling level $level, h = $h, w = $w");
			$max = (int)pow(2, $level);
			$n = 0;
			$y = 0;
			for ($j = 0; $j < $max; $j++) {
				$x = 0;
				for ($i = 0; $i < $max; $i++) {
					echo ".";

					$name = sprintf("images/tile-%d-%d-%d.jpg", $level, $j, $i);

					$tileImage = imagecreatetruecolor(256, 256);
					if (!$status = imagecopyresampled($tileImage, (isset($levelImage)) ? $levelImage : $outImage, 0, 0, $x, $y, 256, 256, 256, 256))
						throw new TilerException("Error tiling level $level", $status);
					imagejpeg($tileImage, $name, 100);
					imagedestroy($tileImage);
					$n++;
					$x += 256;
				}
				$y += 256;
			}
			echo "\n";
			$level--;
			if ($level >= 0) {
				$w = $h = $h / 2;
				$this->log("Shrinking for level $level, h = $h, w = $w");

				$filename = sprintf("images/image%d.jpg", $level);
				$levelImage = imagecreatetruecolor($w, $h);
				if (!$status = imagecopyresampled($levelImage, $outImage, 0, 0, 0, 0, $w, $h, $wMax, $wMax))
					throw new TilerException("Error shrinking level $level", $status);
				imagejpeg($levelImage, $filename, 100);
			}
		}
	}

	private function log($text)
	{
		if ($this->logEnabled)
			echo date("H:i:s") . " $text\n";
	}
}

class TilerException extends Exception
{
}