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
class TilerImagick
{
	private $srcWidth;
	private $srcHeight;
	private $srcFilename;
	private $dstFolder;
	private $logEnabled = false;
	private $fileName = "image";
	private $fileExt = "jpg";
	public $levels;


	/**
	 * @param string $srcFilename Source image
	 * @param string $dstFolder Destination folder for tiles and shrinks
	 * @param bool $log Enable log
	 * @throws TilerException
	 */
	function __construct($srcFilename, $dstFolder, $log = true)
	{
		if (!is_file($srcFilename))
			throw new TilerException("Input file not found", 1);
		$this->srcFilename = $srcFilename;

		$this->fileName = pathinfo($srcFilename,PATHINFO_FILENAME);
		$this->fileExt = pathinfo($srcFilename,PATHINFO_EXTENSION);

		if(!$dstFolder)
			$dstFolder = pathinfo($srcFilename,PATHINFO_DIRNAME);
		if (!is_dir($dstFolder))
			throw new TilerException("Destination folder not found", 1);
		$this->dstFolder = $dstFolder;

		$this->logEnabled = $log;
		$image = new Imagick($this->srcFilename);
		$this->srcWidth = $image->getimagewidth();
		$this->srcHeight = $image->getimageheight();
		$image->destroy();

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
		$filename = sprintf("{$this->dstFolder}/{$this->fileName}-%d.{$this->fileExt}", $level);

		exec("convert {$this->srcFilename} -size {$w}x{$h} -thumbnail {$w}x{$h} -gravity center -extent {$w}x{$h} {$filename}");

		for ($level = $this->levels - 1; $level >= 0;) {
			$this->log("Tiling level $level, h = $h, w = $w");
			$max = (int)pow(2, $level);
			$n = 0;
			$y = 0;
			for ($j = 0; $j < $max; $j++) {
				$x = 0;
				for ($i = 0; $i < $max; $i++) {
					if($this->logEnabled) echo ".";

					$name = sprintf("{$this->dstFolder}/{$this->fileName}-tile-%d-%d-%d.{$this->fileExt}", $level, $j, $i);

					exec("convert $filename -crop 256x256+$x+$y $name");
					$n++;
					$x += 256;
				}
				$y += 256;
			}
			if($this->logEnabled) echo "\n";
			$level--;
			if ($level >= 0) {
				$w = $h = $h / 2;
				$this->log("Shrinking for level $level, h = $h, w = $w");

				$filename = sprintf("{$this->dstFolder}/{$this->fileName}-%d.{$this->fileExt}", $level);
				exec("convert {$this->srcFilename} -size {$w}x{$h} -thumbnail {$w}x{$h} -gravity center -extent {$w}x{$h} {$filename}");
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