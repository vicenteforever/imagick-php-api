<?php
/**
 * imagick图片处理库
 * 
 * @author vicente <wangqiang@xiami.com> 
 * @version 1.0
 * @copyright xiami
 * @created 2013-8-14
 */
class Xiami_ImageMagick {
	
	protected $image = null;
	protected $image_data = array();
	protected $image_type = null;
	protected $light = 100;
	protected $saturation = 100;
	protected $hue = 100;

	/**
	 * 初始化类库
	 **/
	public function __construct($src) {
		try{
			if (!class_exists('Imagick', false)){
				throw new Exception("系统未安装Imagick扩展！"); 
			}
			
			if(!is_file($src) ){
				throw new Exception("源文件已不存在！"); 
			}

			$this->image_data = @getimagesize($src);
			
			if(empty($this->image_data)) {
				throw new Exception("获取图片文件出错");
			}
			
			$im = new Imagick($src); 
			$this->image = $im->clone(); 
			$this->image_type = strtoupper($this->_getFormat());
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}

	/**
	 * 先等比例缩放，然后从中或原点裁切
	 *
	 * @author vicente
	 * @param int $dst_w				目标宽度
	 * @param int $dst_h				目标高度
	 * @param boolean $is_center		图片居中还是从原点裁切
	 * @param int $quality				图片质量
	 * @return string					图片信息
	 * @created 2013-12-9
	 */
	public function resize_two($dst_w, $dst_h, $is_center = true, $quality = 95) {
		try{
			$src_width  = $this->_getWidth();
			$src_height = $this->_getHeight();

			$resize_x   = 0;
			$resize_y   = 0;

			//如果是动画
			if($this->image_type == 'GIF') {
				$color_transparent = new ImagickPixel("transparent"); //透明色
				$dest = new Imagick();

				//imagick本身有出入
				if(!empty($this->image_data)){
					$src_width = $this->image_data[0];
					$src_height = $this->image_data[1];
				}

				$ratio_w    = $dst_w/$src_width;
				$ratio_h    = $dst_h/$src_height;

				foreach ($this->image as $frame){
					$page = $frame->getImagePage();
					$tmp = new Imagick(); 
					$tmp->newImage($page['width'], $page['height'], $color_transparent, 'gif');
					$tmp->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);

					if($ratio_w>1 || $ratio_h>1) {
						if($ratio_w>1 && $ratio_h<1){
							$is_center && $resize_y       = floor(($src_height/2)-($dst_h/2));
							$tmp->cropimage($src_width, $dst_h, $resize_x, $resize_y);
						}else if($ratio_h>1 && $ratio_w<1){
							$is_center && $resize_x = floor(($src_width/2)-($dst_w/2));
							$tmp->cropimage($dst_w, $src_height, $resize_x, $resize_y);
						}else{
							$tmp->cropimage($src_width, $src_height, $resize_x, $resize_y);
						}
					}else if($ratio_w>=$ratio_h){
						$resize_width   = $dst_w;
						$resize_height  = ceil($src_height * $ratio_w);
						$is_center && $resize_y       = floor(($resize_height/2)-($dst_h/2));
	
						$tmp->cropthumbnailimage($resize_width, $resize_height);
						$tmp->cropimage($dst_w, $dst_h, $resize_x, $resize_y);
					}else if($ratio_w<=$ratio_h){
						$resize_width   = ceil($src_width * $ratio_h);
						$resize_height  = $dst_h;
						$is_center && $resize_x       = floor(($resize_width/2)-($dst_w/2));
	
						$tmp->cropthumbnailimage($resize_width, $resize_height);
						$tmp->cropimage($dst_w, $dst_h, $resize_x, $resize_y);
					}

					$dest->addImage($tmp);
					$dest->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
					$dest->setImageDelay($frame->getImageDelay());
					$dest->setImageDispose($frame->getImageDispose());
				}

				$dest->coalesceImages();
				$this->image = $dest;
			}else{
				$ratio_w    = $dst_w/$src_width;
				$ratio_h    = $dst_h/$src_height;

				if($ratio_w>1 || $ratio_h>1){
					if($ratio_w>1 && $ratio_h<1){
						$is_center && $resize_y       = floor(($src_height/2)-($dst_h/2));
						$this->image->cropimage($src_width, $dst_h, $resize_x, $resize_y);
					}else if($ratio_h>1 && $ratio_w<1){
						$is_center && $resize_x = floor(($src_width/2)-($dst_w/2));
						$this->image->cropimage($dst_w, $src_height, $resize_x, $resize_y);
					}else{
						$this->image->cropimage($src_width, $src_height, $resize_x, $resize_y);
					}
				}else if($ratio_w>=$ratio_h){
					$resize_width   = $dst_w;
					$resize_height  = ceil($src_height * $ratio_w);
					$is_center && $resize_y       = floor(($resize_height/2)-($dst_h/2));

					$this->image->cropthumbnailimage($resize_width, $resize_height);
					$this->image->cropimage($dst_w, $dst_h, $resize_x, $resize_y);
				}else if($ratio_w<=$ratio_h){
					$resize_width   = ceil($src_width * $ratio_h);
					$resize_height  = $dst_h;
					$is_center && $resize_x       = floor(($resize_width/2)-($dst_w/2));

					$this->image->cropthumbnailimage($resize_width, $resize_height);
					$this->image->cropimage($dst_w, $dst_h, $resize_x, $resize_y);
				}
			}

			return $this->_save($quality);
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}
	
	/**
	 * 图片等比例缩放，以缩放率小的计算。宽高有一方可以为零，以不为零的一方缩放率计算
	 *
	 * @author vicente
	 * @param int $dst_w				目标宽度
	 * @param int $dst_h				目标高度
	 * @param int $quality				图片质量
	 * @return string					图片信息
	 * @created 2013-12-9
	 */
	public function resize_ratio($dst_w, $dst_h, $quality = 95) {
		try{
			$src_width  = $this->_getWidth();
			$src_height = $this->_getHeight();

			//如果是动画
			if($this->image_type == 'GIF'){
				$color_transparent = new ImagickPixel("transparent"); //透明色
				$dest = new Imagick();

				//imagick本身有出入
				if(!empty($this->image_data)){
					$src_width = $this->image_data[0];
					$src_height = $this->image_data[1];
				}

				$ratio_w    = $dst_w/$src_width;
				$ratio_h    = $dst_h/$src_height;

				if($dst_w >= $src_width && $dst_h >= $src_height){
					$resize_width   = $src_width;
					$resize_height  = $src_height;
				}else if($ratio_h && $ratio_w>=$ratio_h){
					$resize_width   = ceil($src_width * $ratio_h);
					$resize_height  = $dst_h;
				}else if($ratio_w && $ratio_w<=$ratio_h){
					$resize_width   = $dst_w;
					$resize_height  = ceil($src_height * $ratio_w);
				}else if($ratio_w == 0) {
					$resize_width   = ceil($src_width * $ratio_h);
					$resize_height  = $dst_h;
				}else if($ratio_h == 0) {
					$resize_width   = $dst_w;
					$resize_height  = ceil($src_height * $ratio_w);
				}

				foreach ($this->image as $frame){
					$page = $frame->getImagePage();
					$tmp = new Imagick(); 
					$tmp->newImage($page['width'], $page['height'], $color_transparent, 'gif');
					$tmp->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
					$tmp->cropthumbnailimage($resize_width, $resize_height);
					$dest->addImage($tmp);
					$dest->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
					$dest->setImageDelay($frame->getImageDelay());
					$dest->setImageDispose($frame->getImageDispose());
				}

				$dest->coalesceImages();
				$this->image = $dest;
			}else{
				$ratio_w    = $dst_w/$src_width;
				$ratio_h    = $dst_h/$src_height;

				if($dst_w >= $src_width && $dst_h >= $src_height){
					$resize_width   = $src_width;
					$resize_height  = $src_height;
				}else if($ratio_h && $ratio_w>=$ratio_h){
					$resize_width   = ceil($src_width * $ratio_h);
					$resize_height  = $dst_h;
				}else if($ratio_w && $ratio_w<=$ratio_h){
					$resize_width   = $dst_w;
					$resize_height  = ceil($src_height * $ratio_w);
				}else if($ratio_w == 0) {
					$resize_width   = ceil($src_width * $ratio_h);
					$resize_height  = $dst_h;
				}else if($ratio_h == 0) {
					$resize_width   = $dst_w;
					$resize_height  = ceil($src_height * $ratio_w);
				}

				$this->image->cropthumbnailimage($resize_width, $resize_height);
			}

			return $this->_save($quality);
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}

	/**
	 * 从指定位置裁切一定大小图片
	 *
	 * @param int $resizeX 		裁切位置x坐标
	 * @param int $resizeY 		裁切位置y坐标
	 * @param int $width 		裁切宽度
	 * @param int $height 		裁切高度
	 * @param int $quality		图片质量
	 * @return string			图片信息
	 */
	public function crop_resize($resizeX, $resizeY, $resizeWidth, $resizeHeight, $quantity = 95) {
		try{
			$src_width  = $this->_getWidth();
			$src_height = $this->_getHeight();
			
			$leftWidth = $src_width - $resizeX;
			$leftHeight = $resizeHeight - $resizeY;
			
			$resizeWidth = $leftWidth > $resizeWidth ? $resizeWidth : $leftWidth;
			$resizeHeight = $leftHeight > $resizeHeight ? $resizeHeight : $leftHeight;
			
			//如果是动画
			if($this->image_type == 'GIF') {
				$color_transparent = new ImagickPixel("transparent"); //透明色
				$dest = new Imagick();

				foreach ($this->image as $frame){
					$page = $frame->getImagePage();
					$tmp = new Imagick(); 
					$tmp->newImage($page['width'], $page['height'], $color_transparent, 'gif');
					$tmp->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
					
					$tmp->cropimage($resizeWidth, $resizeHeight, $resizeX, $resizeY);

					$dest->addImage($tmp);
					$dest->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
					$dest->setImageDelay($frame->getImageDelay());
					$dest->setImageDispose($frame->getImageDispose());
				}

				$dest->coalesceImages();
				$this->image = $dest;
			}else{
				$this->image->cropimage($resizeWidth, $resizeHeight, $resizeX, $resizeY);
			}

			return $this->_save($quantity);
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}

	/**
	 * 返回扩展名
	 * 
	 * @return string 扩展名
	 */
	public function getExtension() {
		if( $this->image_type == 'JPEG' ) return 'jpg';
		elseif( $this->image_type == 'GIF' ) return 'gif';
		elseif( $this->image_type == 'PNG' ) return 'png';
		elseif( $this->image_type == 'BMP' ) return 'bmp';
	}

	/**
	 * 保存一定质量的图片
	 *
	 * @author vicente
	 * @param int $quality				目标宽度
	 * @return string					返回生成好的图片
	 * @created 2013-12-9
	 */
	private function _save($quality) {
		try{
			$ext = strtolower($this->image_type);
			$tmpPath = $this->getTmpPath($ext);
			$this->image->stripimage();
			$this->image->modulateImage($this->light, $this->saturation, $this->hue);
			$this->image->setImageCompressionQuality($quality);
			if($this->image_type == 'GIF'){
				$this->image->writeImages($tmpPath, true);
			}else{
				$this->image->writeImage($tmpPath);
			}

			$this->image->clear();
			$this->image->destroy();
		
			return $tmpPath;
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}
	
	/**
	 * 生成临时文件
	 *
	 * @author vicente
	 * @param string $ext				扩展名
	 * @param string $tmpFilePrefix		文件前缀
	 * @return string					文件名
	 * @created 2013-12-9
	 */
	public function getTmpPath($ext, $tmpFilePrefix = 'x'){
		return tempnam(sys_get_temp_dir(), $tmpFilePrefix).'.'.$ext;
	}

	/**
	 * 获取图片的高度
	 *
	 * @author vicente
	 * @return int
	 * @created 2013-12-9
	 */
	private function _getHeight() {
		try{
			return $this->image->getImageHeight();
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}

	/**
	 * 获取图片的宽度
	 *
	 * @author vicente
	 * @return int
	 * @created 2013-12-9
	 */
	private function _getWidth() {
		try{
			return $this->image->getImageWidth();
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}

	/**
	 * 获取图片的格式
	 *
	 * @author vicente
	 * @return string
	 * @created 2013-12-9
	 */
	private function _getFormat() {
		try{
			return $this->image->getImageFormat();
		}catch(Exception $ex){
			echo $ex->getMessage();
			exit;
		}
	}
	
}
