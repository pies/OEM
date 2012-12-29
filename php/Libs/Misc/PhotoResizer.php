<?php
namespace Misc;

use \Imagick;

class PhotoResizeImagick {
	
	/**
	 * @var Imagick
	 */
	private $img;
	
	private $filter = Imagick::FILTER_CATROM;
	
	public function __construct($path) {
		$this->img = new \Imagick(realpath($path));
	}
	
	/**
	 * @param int $new_x
	 * @param int $new_y
	 * @param string $method
	 * @param string $type
	 * @return Imagick
	 */
	public function get($new_x=0, $new_y=0, $method='x', $type=false) {
		if ($type) {
			$this->img->setImageFormat($type);
		}
		
		if ($method == 'm') {
			return $this->getLimitedTo($new_x, $new_y);
		}
		elseif ($method == 'x' && $new_x && $new_y) {
			return $this->getCutDownTo($new_x, $new_y);
		}
		elseif ($method == 'x' && (!$new_x || !$new_y)) {
			return $this->getScaledTo($new_x, $new_y);
		}
		else {
			die("Incorrect parameters: method[{$method}], new_x[{$new_x}], new_y[{$new_y}]");
		}
	}
	
	private function scaleLimits($orig_x, $orig_y, $max_x, $max_y) {
	    //Set the default NEW values to be the old, in case it doesn't even need scaling
		list($new_x, $new_y) = array($orig_x, $orig_y);

		//If image is generally smaller, don't even bother
		if ($orig_x >= $max_x || $orig_y >= $max_y) {

			//Work out ratios
			if ($orig_x > 0) {
				$ratio_x = $max_x / $orig_x;
			}
			
			if ($orig_y > 0) {
				$ratio_y = $max_y / $orig_y;
			}

			//Use the lowest ratio, to ensure we don't go over the wanted image size
			if ($ratio_x > $ratio_y) {
				$ratio = $ratio_y;
			}
			else {
				$ratio = $ratio_x;
			}

			//Calculate the new size based on the chosen ratio
			$new_x = intval($orig_x * $ratio);
			$new_y = intval($orig_y * $ratio);
		}

		//Return the results
		return array($new_x, $new_y);
	}
	
	private function resizeAnimation($new_x, $new_y) {
		$this->img = $this->img->coalesceImages();
		do {
			$this->img->resizeImage($new_x, $new_y, $this->filter, 1);
		}
		while ($this->img->nextImage());
		$this->img = $this->img->deconstructImages();
	}
	
	public function getLimitedTo($max_x=0, $max_y=0) {
		$orig_x = $this->width();
		$orig_y = $this->height();
		
		list($new_x, $new_y) = $this->scaleLimits($orig_x, $orig_y, $max_x, $max_y);
		
		$format = $this->img->getImageFormat();
		if ($format == 'gif') {
			$this->resizeAnimation($new_x, $new_y);
			return $this->img->getImagesBlob();
		}
		else {
			$this->img->resizeimage($new_x, $new_y, $this->filter, 1);
			$this->img->thumbnailImage($new_x, $new_y);
			return $this->img->getImageBlob();
		}
	}
	
	public function width() {
		return $this->img->getimagewidth();
	}
	
	public function height() {
		return $this->img->getimageheight();
	}
	
	public function getCutDownTo($new_x=0, $new_y=0) {
		$orig_x = $this->width();
		$orig_y = $this->height();
		
		$ratio_x = $orig_x / $new_x;
		$ratio_y = $orig_y / $new_y;
		$copy_x = $orig_x;
		$copy_y = $orig_y;
		$offset_x = 0;
		$offset_y = 0;
		
		if ($ratio_x < $ratio_y) { 
			// original too tall
			$copy_y = $new_y * $ratio_x;
			// slightly above center
			$offset_y = round(($orig_y - $copy_y) / 2.2); 
		}
		else { 
			// original too wide
			$copy_x = $new_x * $ratio_y;
			$offset_x = round(($orig_x - $copy_x) / 2);
		}
		
		$this->img->cropimage($copy_x, $copy_y, $offset_x, $offset_y);
		
		$this->img->resizeimage($new_x, $new_y, $this->filter, 1);
		//$this->img->thumbnailImage($new_x, $new_y);
		
		return $this->img->getimageblob();
	}
	
	public function getScaledTo($new_x=0, $new_y=0) {
		$orig_x = $this->width();
		$orig_y = $this->height();
		
		if ($new_x == 0) { // guess width
			$new_x = round(($new_y / $orig_y) * $orig_x);
		}
		elseif ($new_y == 0) { // guess height
			$new_y = round(($new_x / $orig_x) * $orig_y);
		}
	
		$this->img->resizeimage($new_x, $new_y, $this->filter, 1);
		//$this->img->thumbnailImage($new_x, $new_y);
		
		return $this->img->getimageblob();
	}

	public function getFormat() {
		return $this->img->getFormat();
	}

	
}

class PhotoResizer extends PhotoResizeImagick {}

























// Example:
//   $thumb = new PhotoResizer($full_path_to_image);
//   header('Content-type: image/jpeg');
//   die($thumb->get($width, $height, 'jpeg'));

// Use 0 as $width or $height to calculate it automatically
// Output image can have a different shape than the original

class PhotoResizerSource {

	/**
	 * @var string
	 */
	public $path;
	
	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var int
	 */
	public $w;
	
	/**
	 * @var int
	 */
	public $h;
	
	/**
	 * @var resource
	 */
	public $image;

	public function __construct($path) {
		$this->path = $path;

		if (!file_exists($this->path)) {
			throw new \Exception("File not found: {$this->path}");
		}

		list($this->w, $this->h, $type) = getimagesize($this->path);
		$this->setType($type);

		$this->image = $this->load();
		if (!$this->image) {
			throw new \Exception("Could not open: {$this->path}");
		}
	}

	private function setType($type) {
		switch ($type) {
			case IMAGETYPE_GIF:  $this->type = 'gif'; break;
			case IMAGETYPE_JPEG: $this->type = 'jpeg'; break;
			case IMAGETYPE_PNG:  $this->type = 'png'; break;
			default: $this->type = null;
		}

		if (!$this->type) {
			throw new \Exception("Unnown image type: {$type}");
		}
	}

	private function load() {
		switch ($this->type) {
			case 'gif':  return imagecreatefromgif($this->path);
			case 'jpeg': return imagecreatefromjpeg($this->path);
			case 'png':  return imagecreatefrompng($this->path);
			default: return false;
		}
	}
}

class PhotoResizerGD {

	/**
	 * @var int
	 */
	public $w;
	
	/**
	 * @var int
	 */
	public $h;
	
	/**
	 * @var PhotoResizerSource
	 */
	public $src;

	/**
	 * @var resource
	 */
	public $image;

	public function __construct($path) {
		$this->src = new PhotoResizerSource($path);
	}

	public function get($w=0, $h=0, $type=false, $method='x') {
		if (!$type) $type = $this->src->type;
		$this->resize($w, $h, $method);
		ob_start();
		switch ($type) {
			case 'gif':  imagegif($this->image); break;
			case 'jpeg': imagejpeg($this->image, null, 85); break;
			case 'png':  imagepng($this->image); break;
		}
		return ob_get_clean();
	}

	private function resize($width, $height, $method='x') {
		if ($width > 2000 || $height > 1200) {
			throw new \Exception("Thumbnail size too large: {$width}x{$height}");
		}

		if (!$width && !$height) {
			$width = $this->src->w;
			$height = $this->src->h;
		}

		if ($width == 0) { // guess width
			$width = round(($height / $this->src->h) * $this->src->w);
		}
		elseif ($height == 0) { // guess height
			$height = round(($width / $this->src->w) * $this->src->h);
		}

		$ratio_w = $this->src->w / $width;
		$ratio_h = $this->src->h / $height;
		$copy_w = $this->src->w;
		$copy_h = $this->src->h;
		$offset_w = 0;
		$offset_h = 0;
		
		/**
		 * Method 'x' forces image into a specific set of dimensions, cuts off 
		 * the rest.
		 * 
		 * Method 'm' limits image to a set of dimensions, but doesn't enforce, 
		 * just scales.
		 */
		
		if ($ratio_w < $ratio_h) { // original too tall
			if ($method == 'x') {
				$copy_h = $height * $ratio_w;
				$offset_w = round(($this->src->h - $copy_h) / 2.2); // slightly above center
			}
			elseif ($method == 'm') {
				$width = round($this->src->w * ($height/$this->src->h));
			}
		}
		elseif ($ratio_w > $ratio_h) { // original too wide
			if ($method == 'x') {
				$copy_w = $width * $ratio_h;
				$offset_h = round(($this->src->w - $copy_w) / 2);
			}
			elseif ($method == 'm') {
				$height = round($this->src->h * ($width/$this->src->w));
			}
		}

		if (($method == 'm') && (($width > $this->src->w) || ($height > $this->src->h))) {
			$width = $this->src->w;
			$height = $this->src->h;
		}
		
		$this->image = imagecreatetruecolor($width, $height);
		
		// This is the resizing/resampling/transparency-preserving magic
		// https://github.com/maxim/smart_resize_image/blob/master/smart_resize_image.function.php
		$type = $this->src->type;		
		if (($type == 'gif') || ($type == 'png')) {
			$transparency = imagecolortransparent($this->src->image);

			if (($transparency >= 0) && ($transparency <= 254)) {
				$transparent_color = imagecolorsforindex($this->src->image, $transparency);
				$transparency = imagecolorallocate($this->image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
				imagefill($this->image, 0, 0, $transparency);
				imagecolortransparent($this->image, $transparency);
			}
			elseif ($type == 'png') {
				imagealphablending($this->image, false);
				$color = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
				imagefill($this->image, 0, 0, $color);
				imagesavealpha($this->image, true);
			}
		}

		imagecopyresampled($this->image, $this->src->image, 0, 0, $offset_h, $offset_w, $width, $height, $copy_w, $copy_h);
	}

	public function getUri($w=0, $h=0, $type=false) {
		if (!$type) $type = $this->src->type;
		return "data:image/{$mime};base64,".base64_encode($this->get($w, $h, $type));
	}

}
