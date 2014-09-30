<?php

/**
 * Requires php 5.4+ 
 * 
 * Easy image is an image proccessing library intended to provide some simple image 
 * conversions, abstracting the image file type. 
 * 
 * 
 * @author Nick Blackwell https://people.ok.ubc.ca/nblackwe
 * 
 */
 
 
class EasyImage{

	private static $exception='';
	public static function LastError(){
		return EasyImage::$exception;
	}
	public static function Open($path){
		
		$p_ex=explode('.', $path);
		$p_po=array_pop($p_ex);
		
		$ext=strtolower($p_po);
		if(file_exists($path)){
		
			
			switch($ext){
				case 'jpeg':
				case 'jpg':return imagecreatefromjpeg($path);
				case 'png':return imagecreatefrompng($path);
				case 'gif':return imagecreatefromgif($path);
				case 'bmp':
					$createfrombmp=function( $filename )
					{
						
						/*
						 * this needs to be rewritten
						 */
						
						$file = fopen( $filename, "rb" );
						$read = fread( $file, 10 );
						while( !feof( $file ) && $read != "" )
						{
							$read .= fread( $file, 1024 );
						}
						$temp = unpack( "H*", $read );
						$hex = $temp[1];
						$header = substr( $hex, 0, 104 );
						$body = str_split( substr( $hex, 108 ), 6 );
						if( substr( $header, 0, 4 ) == "424d" )
						{
							$header = substr( $header, 4 );
							// Remove some stuff?
							$header = substr( $header, 32 );
							// Get the width
							$width = hexdec( substr( $header, 0, 2 ) );
							// Remove some stuff?
							$header = substr( $header, 8 );
							// Get the height
							$height = hexdec( substr( $header, 0, 2 ) );
							unset( $header );
						}
						$x = 0;
						$y = 1;
						$image = imagecreatetruecolor( $width, $height );
						foreach( $body as $rgb )
						{
							$r = hexdec( substr( $rgb, 4, 2 ) );
							$g = hexdec( substr( $rgb, 2, 2 ) );
							$b = hexdec( substr( $rgb, 0, 2 ) );
							$color = imagecolorallocate( $image, $r, $g, $b );
							imagesetpixel( $image, $x, $height-$y, $color );
							$x++;
							if( $x >= $width )
							{
								$x = 0;
								$y++;
							}
						}
						return $image;
					};
					
					
					return $createfrombmp($path);
			}
			
			EasyImage::$exception="Invalid Image Type [jpeg, jpg, png, gif, bmp], ".$path;
			return false;
		
		}else{
			EasyImage::$exception="File Not Found, ".$path;
			return false;
		}
		
	}
	
	public static function OpenContent($str){
		
		return imagecreatefromstring($str);
		
	}
	
	public static function ColorProfile($image){
		$simplified=EasyImage::Thumbnail($image, 10);
		//imagetruecolortopalette($simplified, false, 5);
		$s=EasyImage::GetSize($simplified);
		$values=array();
		for($x=0;$x<$s['w'];$x++){	
			for($y=0;$y<$s['h'];$y++){
				$rgb=imagecolorat($simplified, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$values[]='rgb('.$r.', '.$g.', '.$b.')';
			}
		}
		
		
		/**
		 * TODO: return color information about the image, metadata, with main colors?
		 */	
		return array('colors'=>$values);
	}
	
	public static function DetectBoundary($image){
		
		/**
		 * TODO: return an array of coordinates maping the visual boundary of the image if transparencies exist
		 */	
		return array();
	}
	
	
	
	
	public static function FilterGrayScale($image){
		imagefilter($image, IMG_FILTER_GRAYSCALE);
		
	}
	public static function FilterBrightness($image, $amount){
		imagefilter($image, IMG_FILTER_BRIGHTNESS, $amount);
	}
	public static function Thumbnail($image, $x, $y=false, $scale=true){

		if(!$y)$y=$x;
		
		
		
		$width=imagesx($image); 
		$height=imagesy($image);
		
		$outW=$width;
		$outY=$height;
		
		
		
		
		if($scale){
		
			if($x<$outW){
				$outY=$height*($x/$width);
				$outW=$x;
			}
			if($y<$outY){
				$outW=$width*($y/$height);
				$outY=$y;
			}
		}else{
			$outW=$x;
			$outY=$y;
		}
		
		
		
		$out=imagecreatetruecolor($outW,$outY);
		imagefill($out,0,0,imagecolortransparent($out,imagecolorallocate($out,0,0,0)));
		imagesavealpha($out, true);
		imagealphablending($out,false);
		imagecopyresampled($out,$image,0,0,0,0,$outW,$outY,$width ,$height);
		return $out;
	}
	public static function Close($image){
		imagedestroy($image);
	}
	/**
	 * 
	 * @param unknown $image
	 * @return array with (x,y) keys
	 */
	public static function GetSize($image){
		return array('w'=>imagesx($image), 'h'=>imagesy($image));
	}
	
	/**
	 * TODO: make use of this method. and test.
	 * @param image resource $top
	 * @param image resource $bottom
	 * @param unknown_type $x
	 * @param unknown_type $y
	 */
	public static function Overlay($top, $bottom, $x, $y=false){
		if($y===false)$y=$x;
		imagealphablending($top,true);
		imagealphablending($bottom,true);
		imagecopy($bottom, $top, $x, $y, 0, 0, imagesx($bottom),imagesy($bottom));
		return $bottom;
	}
	
	public static function Save($image, $path){
		$ext_ex=explode('.', $path);
		$ext=strtolower(array_pop($ext_ex));
		if(in_array($ext,array('jpg', 'jpeg', 'png', 'gif'))){
			switch($ext){	
				case 'jpeg':
				case 'jpg':return imagejpeg($image, $path);
				case 'png':return imagepng($image, $path);
				case 'gif':return imagegif($image, $path);
			}
		}
		
		
		
		
	}

}