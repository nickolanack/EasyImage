<?php

/**
 * Requires php 5.4+
 *
 * Easy image is an image proccessing library intended to provide some simple image
 * conversions, abstracting the image file type.
 *
 * This class contains static methods.
 *
 *
 * @author Nick Blackwell https://people.ok.ubc.ca/nblackwe
 *
 */
class EasyImage {

    public static function Open($path) {
        $p_ex = explode('.', $path);
        $p_po = array_pop($p_ex);
        
        $ext = strtolower($p_po);
        if (file_exists($path)) {
            
            try {
                switch ($ext) {
                    case 'jpeg':
                    case 'jpg':
                        return imagecreatefromjpeg($path);
                    case 'png':
                        return imagecreatefrompng($path);
                    case 'gif':
                        return imagecreatefromgif($path);
                    case 'bmp':
                        $createfrombmp = function ($p_sFile) {
                            
                            /*
                             * http://php.net/manual/en/function.imagecreatefromwbmp.php#86214
                             */
                            
                           $file    =    fopen($p_sFile,"rb");
                            $read    =    fread($file,10);
                            while(!feof($file)&&($read<>""))
                                $read    .=    fread($file,1024);
                            $temp    =    unpack("H*",$read);
                            $hex    =    $temp[1];
                            $header    =    substr($hex,0,108);
                            if (substr($header,0,4)=="424d")
                            {
                                $header_parts    =    str_split($header,2);
                                $width            =    hexdec($header_parts[19].$header_parts[18]);
                                $height            =    hexdec($header_parts[23].$header_parts[22]);
                                unset($header_parts);
                            }
                            $x                =    0;
                            $y                =    1;
                            $image            =    imagecreatetruecolor($width,$height);
                            $body            =    substr($hex,108);
                            $body_size        =    (strlen($body)/2);
                            $header_size    =    ($width*$height);
                            $usePadding        =    ($body_size>($header_size*3)+4);
                            for ($i=0;$i<$body_size;$i+=3)
                            {
                                if ($x>=$width)
                                {
                                    if ($usePadding)
                                        $i    +=    $width%4;
                                    $x    =    0;
                                    $y++;
                                    if ($y>$height)
                                        break;
                                }
                                $i_pos    =    $i*2;
                                $r        =    hexdec($body[$i_pos+4].$body[$i_pos+5]);
                                $g        =    hexdec($body[$i_pos+2].$body[$i_pos+3]);
                                $b        =    hexdec($body[$i_pos].$body[$i_pos+1]);
                                $color    =    imagecolorallocate($image,$r,$g,$b);
                                imagesetpixel($image,$x,$height-$y,$color);
                                $x++;
                            }
                            unset($body);
                            return $image;
                        };
                        
                        return $createfrombmp($path);
                }
            } catch (Exception $e) {
                throw new Exception('EasyImage: Failed to read image (' . $e->getMessage() . '): ' . $path);
            }
            
            throw new Exception('EasyImage: Invalid Image Type, not one of [jpeg, jpg, png, gif, bmp]: ' . $path);
        } else {
            throw new Exception("EasyImage: File not found: " . $path);
        }
    }

    public static function OpenContent($str) {
        return imagecreatefromstring($str);
    }

    private static function _rgbParse($rgb) {
        return $rgb;
    }

    /**
     *
     * @param array $rgb
     *            array(0,0,0) = black...
     * @param number $threshhold            
     * @return boolean if every pixel is aproximately equal to $rgb this method scales the image so
     *         that pixels may be blended before compared
     *        
     */
    public static function IsAllColor($image, $rgb, $threshhold = 0) {
        $simplified = EasyImage::ThumbnailFit($image, 10);
        // imagetruecolortopalette($simplified, false, 5);
        $s = EasyImage::GetSize($simplified);
        
        $rgb = EasyImage::_rgbParse($rgb);
        
        for ($x = 0; $x < $s['w']; $x ++) {
            for ($y = 0; $y < $s['h']; $y ++) {
                $rgb = imagecolorat($simplified, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                if (abs($r - $rgb[0]) > $threshhold)
                    return false;
                if (abs($g - $rgb[1]) > $threshhold)
                    return false;
                if (abs($b - $rgb[2]) > $threshhold)
                    return false;
            }
        }
        return true;
    }

    public static function IsAllOneColor($image, $threshhold = 0) {
        $simplified = EasyImage::ThumbnailFit($image, 10);
        // imagetruecolortopalette($simplified, false, 5);
        $s = EasyImage::GetSize($simplified);
        for ($x = 0; $x < $s['w']; $x ++) {
            for ($y = 0; $y < $s['h']; $y ++) {
                
                $rgb = imagecolorat($simplified, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if (! $rgb) {
                    $rgb = array(
                        $r,
                        $g,
                        $b
                    );
                }
                
                if (abs($r - $rgb[0]) > $threshhold)
                    return false;
                if (abs($g - $rgb[1]) > $threshhold)
                    return false;
                if (abs($b - $rgb[2]) > $threshhold)
                    return false;
            }
        }
        return true;
    }

    public static function ColorProfile($image) {
        $simplified = EasyImage::ThumbnailFit($image, 10);
        // imagetruecolortopalette($simplified, false, 5);
        $s = EasyImage::GetSize($simplified);
        $values = array();
        for ($x = 0; $x < $s['w']; $x ++) {
            for ($y = 0; $y < $s['h']; $y ++) {
                $rgb = imagecolorat($simplified, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $values[] = 'rgb(' . $r . ', ' . $g . ', ' . $b . ')';
            }
        }
        
        /**
         * TODO: return color information about the image, metadata, with main colors?
         */
        return array(
            'colors' => $values
        );
    }

    public static function DetectBoundary($image) {
        
        /**
         * TODO: return an array of coordinates maping the visual boundary of the image if transparencies exist
         */
        return array();
    }

    public static function FilterGrayScale($image) {
        imagefilter($image, IMG_FILTER_GRAYSCALE);
    }

    public static function FilterBrightness($image, $amount) {
        imagefilter($image, IMG_FILTER_BRIGHTNESS, $amount);
    }

    /**
     * TODO: similar to ThumbnailFit, but will crop to size maintaining aspect ratio
     */
    public static function ThumbnailFill($image, $x, $y = false, $scale = true) {
        throw new Exception('EasyImage: Not implemented: (ThumbnailFill)');
    }

    /**
     * scales an image, given a image resource $image so that it fits entirely within $x, $y (width, height) and
     * maintains aspect ratio
     *
     * @param resource $image            
     * @param int $x
     *            width
     * @param int $y
     *            height (or null for $x=$y)
     * @param boolean $scale
     *            ignore this arg
     * @return resource a new image resource. call EasyImage::Close($oldResource) if done with the previous
     */
    public static function ThumbnailFit($image, $x, $y = false, $scale = true) {
        if (! $y)
            $y = $x;
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        $outW = $width;
        $outY = $height;
        
        if ($scale) {
            
            if ($x < $outW) {
                $outY = $height * ($x / $width);
                $outW = $x;
            }
            if ($y < $outY) {
                $outW = $width * ($y / $height);
                $outY = $y;
            }
        } else {
            $outW = $x;
            $outY = $y;
        }
        
        $out = imagecreatetruecolor($outW, $outY);
        imagefill($out, 0, 0, imagecolortransparent($out, imagecolorallocate($out, 0, 0, 0)));
        imagesavealpha($out, true);
        imagealphablending($out, false);
        imagecopyresampled($out, $image, 0, 0, 0, 0, $outW, $outY, $width, $height);
        return $out;
    }

    public static function Close($image) {
        imagedestroy($image);
    }

    /**
     *
     * @param unknown $image            
     * @return array with (w,h) keys
     */
    public static function GetSize($image) {
        $x = imagesx($image);
        $y = imagesy($image);
        
        return array(
            'w' => $x,
            'h' => $y
        );
    }

    /**
     * overlays two images ($img1 on top of $img2 at [$xOffset, $yOffset])
     *
     *
     * TODO: make use of this method. and test.
     *
     * @param resource $img1
     *            a GD image resource
     * @param resource $img2
     *            a GD image resource
     * @param int $xOffset            
     * @param int $yOffset            
     */
    public static function Overlay($img1, $img2, $xOffset, $yOffset = false) {
        if ($yOffset === false)
            $yOffset = $xOffset;
        imagealphablending($img1, true);
        imagealphablending(img2, true);
        imagecopy(img2, $img1, $xOffset, $yOffset, 0, 0, imagesx(img2), imagesy(img2));
        return img2;
    }

    /**
     * writes image resource to file.
     *
     * @param resource $image            
     * @param string $path
     *            file type will be detected from file extension
     * @return boolean true on success.
     */
    public static function Save($image, $path) {
        $ext_ex = explode('.', $path);
        $ext = strtolower(array_pop($ext_ex));
        if (in_array($ext, array(
            'jpg',
            'jpeg',
            'png',
            'gif'
        ))) {
            switch ($ext) {
                case 'jpeg':
                case 'jpg':
                    return imagejpeg($image, $path);
                case 'png':
                    return imagepng($image, $path);
                case 'gif':
                    return imagegif($image, $path);
            }
        }
    }

    /**
     * replaces all colors in an image with $rgb, conserving alpha
     *
     * @param resource $image
     *            image resource eg: EasyImage::Open
     * @param array $rgb
     *            [int:red, int:green, int:blue] tint color
     * @return resource a new resource for the tinted image
     */
    public static function Tint($image, $rgb) {
        $s = EasyImage::GetSize($image);
        
        $tinted = imagecreatetruecolor($s['w'], $s['h']);
        $color = imagecolorallocatealpha($tinted, $rgb[0], $rgb[1], $rgb[2], 127);
        
        imagefill($tinted, 0, 0, $color);
        
        for ($x = 0; $x < $s['w']; $x ++) {
            for ($y = 0; $y < $s['h']; $y ++) {
                
                $a = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $t = imagecolorsforindex($tinted, imagecolorat($tinted, $x, $y));
                
                imagesetpixel($tinted, $x, $y, 
                    imagecolorallocatealpha($tinted, $t['red'], $t['green'], $t['blue'], $a['alpha']));
            }
        }
        
        // neccessary for transparency
        imageAlphaBlending($tinted, true);
        imageSaveAlpha($tinted, true);
        
        return $tinted;
    }

    /**
     * replaces all colors in an image with an $rgb that slightly transitions, conserving alpha
     *
     * @param resource $image
     *            image resource eg: EasyImage::Open
     * @param array $rgb
     *            [int:red, int:green, int:blue] tint color
     * @return resource a new resource for the tinted image
     */
    public static function TintFade($image, $rgb) {
        $s = EasyImage::GetSize($image);
        
        $tinted = imagecreatetruecolor($s['w'], $s['h']);
        
        $span = 0.3;
        $end = 1.15;
        
        // adjust fade start end colors to within 255 limit
        foreach ($rgb as $c) {
            if ($c * $end > 255) {
                $end = 255.0 / c;
            }
        }
        
        $start = $end - $span;
        $step = $span / $s['h'];
        // header('Content-Type: text/html;');
        imageAlphaBlending($tinted, false);
        for ($y = 0; $y < $s['h']; $y ++) {
            
            $color = imagecolorallocatealpha($tinted, (int) $rgb[0] * ($start + ($step * $y)), 
                (int) $rgb[1] * ($start + ($step * $y)), (int) $rgb[2] * ($start + ($step * $y)), 127);
            if (! imageline($tinted, 0, $y, $s['w'], $y, $color)) {
                // echo 'failed';
            }
            
            // print_r('(0, '.$y.', '.$s['w'].', '.($y+1).') '.$rgb[0]*($start+($step*$y)).' - '.$rgb[1]*($start+($step*$y)).' - '.$rgb[2]*($start+($step*$y))."<br/>");
        }
        ;
        
        // die();
        for ($x = 0; $x < $s['w']; $x ++) {
            for ($y = 0; $y < $s['h']; $y ++) {
                
                $a = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $t = imagecolorsforindex($tinted, imagecolorat($tinted, $x, $y));
                
                imagesetpixel($tinted, $x, $y, 
                    imagecolorallocatealpha($tinted, $t['red'], $t['green'], $t['blue'], $a['alpha']));
            }
        }
        
        // neccessary for transparency
        // imageAlphaBlending($tinted, true);
        imageSaveAlpha($tinted, true);
        
        return $tinted;
    }
}