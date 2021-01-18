<?php

/**
 * Class to make a image mosaic of an image with diffrent shapes
 * 
 * Created by
 * Daniel Gomez-Ortega
 */
class Mosaic {
    private $existing_shapes = ['circle', 'smoothcircle', 'square', 'star'];
    private $image_data = [];
    private $image_filename = "";
    private $image_file = null;
    private $image_sampled_pixels = [[]];
    public $sample_size = 20;
    public $shape_size = 40;
    public $shape_margin = 3;
    private $mosaic_shape = "circle";
    private $mosaic_image = null;

    /**
     * Class constructor
     * 
     * @param string $filename Path to image file
     */
    public function __construct($filename){
        $this->image_filename = $filename;

        //get image file
        $this->image_file = $this->get_image($this->image_filename);

        //get image size: width, height
        $this->get_image_size();
    }

    /**
     * Method to read image file and create an image object
     */
    private function get_image(){
        if(!file_exists($this->image_filename)){
            throw new Exception('Image file not found.');
        }

        //return image objekt
        return imagecreatefromstring(file_get_contents($this->image_filename));
    }
    /**
     * Method to get image width and height
     * Rescale image to max 1024px in width if large
     */
    private function get_image_size(){
        //get image width and height
        $this->image_data['width'] = imagesx($this->image_file);
        $this->image_data['height'] = imagesy($this->image_file);
        
        //check image size - Avoid possible "Out of memory" situations
        if( $this->image_data['width'] > 1024 ){
            $this->image_file = imagescale( $this->image_file, 1024);
            //re get size
            $this->image_data['width'] = imagesx($this->image_file);
            $this->image_data['height'] = imagesy($this->image_file);
        }

        if( !array_key_exists('width', $this->image_data)){
            throw new Exception('Could not get width of image.');
        }
        if( !array_key_exists('height', $this->image_data)){
            throw new Exception('Could not get height of image.');
        }
        if( $this->image_data['width'] == 0 || $this->image_data['height'] == 0 ){
            throw new Exception('Could not get height of image.');
        }
    }

    /**
     * Create mosaic of the image
     * 
     * @param string Shape of mosaic tile to use, values: circle, smoothcircle, square, star
     */
    public function create_mosaic($shape, $no_output = false, $save_to_file = false){
        if( in_array($shape, $this->existing_shapes)){
            $this->mosaic_shape = $shape;
        }else{
            throw new Exception('Not a valid shape.');
        }

        //destory old image if called multiple times
        if(!empty($this->mosaic_image)){
            @imagedestroy($this->mosaic_image);
        }

        //read colors in sampled pixels
        $this->sample_pixel_colors($this->sample_size);
        
        //create mosaic
        $this->mosaic_image = $this->create_mosaic_image();
        
        //output image
        if(!$no_output){
            header("Content-type: image/png");
            imagepng($this->mosaic_image);
        }

        //save file
        if($save_to_file){
            $this->save_mosaic("");
        }
    }

    /**
     * Save mosaic image to file
     * 
     * @return bool Status of save
     */
    public function save_mosaic($save_filename = ""){
        if(!empty($this->mosaic_image)){
            $save_as = "mosaic_" . $this->mosaic_shape . "_" . date("ymdHis") . ".png";
            if( !empty($save_filename) ){
                if(substr($save_filename, -4) !== ".png"){
                    $save_filename .= ".png";
                }
                $save_as = $save_filename;
            }
            return imagepng($this->mosaic_image, __DIR__ . "/" . $save_as);
        }
    }

    /**
     * Loop all pixels in image and sample color of pixel and save it
     * 
     * @param int $sample_size Sample intervall
     */
    private function sample_pixel_colors($sample_size){
        $y_pixel = 0;
        for($y = 0; $y < $this->image_data['height']; $y += $sample_size ) {
            $x_pixel = 0;
            for($x = 0; $x < $this->image_data['width']; $x += $sample_size ) {
                //get color at pixel x,y
                $rgb = imagecolorat($this->image_file, $x, $y);
                $r = (($rgb >> 16) & 0xFF);
                $g = (($rgb >> 8) & 0xFF);
                $b = ($rgb & 0xFF);
                
                //save pixel colors
                $this->image_sampled_pixels[$y_pixel][$x_pixel] = [$r,$g,$b];
                
                $x_pixel++;
            }
            $y_pixel++;
        }
    }

    /**
     * Create the mosaic image with set shape
     * 
     * @return image
     */
    private function create_mosaic_image(){
        //calculate new size of mosaic image
        $new_width = ceil($this->image_data['width'] / $this->sample_size) * ($this->shape_size + $this->shape_margin);
        $new_height = ceil($this->image_data['height'] / $this->sample_size) * ($this->shape_size + $this->shape_margin);

        //create mosaic image
        $image = imagecreatetruecolor($new_width, $new_height);
        //set antialias 
        imageantialias($image, true);

        //set white background
        $white = imagecolorallocate($image, 255, 255, 255); 
        imagefilltoborder($image, 0, 0, $white, $white);

        //loop all sampled pixels
        $height_sampled_pixels = count($this->image_sampled_pixels);
        $width_sampled_pixels = count($this->image_sampled_pixels[0]);
        for($y = 0; $y < $height_sampled_pixels; $y++ ) {
            for($x = 0; $x < $width_sampled_pixels; $x++ ) {

                if($this->mosaic_shape == "square"){
                    imagefilledrectangle($image, $x*($this->shape_size + $this->shape_margin) , $y*($this->shape_size + $this->shape_margin), 
                        $x*($this->shape_size + $this->shape_margin) + $this->shape_size, 
                        $y*($this->shape_size + $this->shape_margin) + $this->shape_size, 
                        imagecolorallocate($image, 
                        $this->image_sampled_pixels[$y][$x][0], 
                        $this->image_sampled_pixels[$y][$x][1], 
                        $this->image_sampled_pixels[$y][$x][2])
                    );
                }

                if($this->mosaic_shape == "circle"){
                    //ugly antialias
                    imagefilledellipse($image, 
                        $x*($this->shape_size + $this->shape_margin) + ($this->shape_size/2), //x center pos = x sample * (circle size + margin) + half circle
                        $y*($this->shape_size + $this->shape_margin) + ($this->shape_size/2), //y center pos = y sample * (circle size + margin) + half circle
                        $this->shape_size, $this->shape_size, 
                        imagecolorallocate($image, 
                            $this->image_sampled_pixels[$y][$x][0], 
                            $this->image_sampled_pixels[$y][$x][1], 
                            $this->image_sampled_pixels[$y][$x][2]
                        )
                    );
                }

                if($this->mosaic_shape == "smoothcircle"){
                    //nice antialias
                    $this->imageSmoothCircle( $image, 
                        $x*($this->shape_size + $this->shape_margin) + ($this->shape_size/2), //width = number of samples * (circle size + margin)
                        $y*($this->shape_size + $this->shape_margin) + ($this->shape_size/2), //height = number of samples * (circle size + margin)
                        $this->shape_size/2, //radius
                        array( 
                            'R' => $this->image_sampled_pixels[$y][$x][0], 
                            'G' => $this->image_sampled_pixels[$y][$x][1], 
                            'B' => $this->image_sampled_pixels[$y][$x][2] 
                        ) 
                    );
                }

                if($this->mosaic_shape == "star"){
                    $this->imageFilledStar( $image, 
                    $x*($this->shape_size + $this->shape_margin) + ($this->shape_size/2), 
                    $y*($this->shape_size + $this->shape_margin) + ($this->shape_size/2), 
                    ($this->shape_size + $this->shape_margin), 
                        imagecolorallocate($image, 
                            $this->image_sampled_pixels[$y][$x][0], 
                            $this->image_sampled_pixels[$y][$x][1], 
                            $this->image_sampled_pixels[$y][$x][2]
                        )
                    );
                }
                
            }
        }

        return $image;
    }

    /**
     * Draw a star on image
     * 
     * @param image &$img GD image resource
     * @param int $x X position of star
     * @param int $y Y position of star
     * @param int $size Size of star
     * @param int $color Color of star
     */
    private function imageFilledStar( &$img, $x, $y, $size, $color ){
        $cords = $this->drawStar($x, $y, $size/2);
        imagefilledpolygon($img, $cords, count($cords)/2, $color);
    }

    
    /** 
     * Create cords for a star polygon
     * 
     * drawStar or regular polygon
     * $x, $y  -> Position in the image
     * $radius -> Radius of the star
     * $spikes -> Number of spikes (min 2)
     * $ratio  -> Ratio between outer and inner points
     * $dir    -> Rotation 270° for having an up spike( with ratio<1)
     *   
     * See more info
     * https://www.php.net/manual/en/function.imagefilledpolygon.php
    */
    private function drawStar($x, $y, $radius, $spikes=5, $ratio=0.5, $dir=270) {
        $coordinates = array();
        $angle = 360 / $spikes ;
        for($i=0; $i<$spikes; $i++){
            $coordinates[] = $x + (       $radius * cos(deg2rad($dir+$angle*$i)));
            $coordinates[] = $y + (       $radius * sin(deg2rad($dir+$angle*$i)));
            $coordinates[] = $x + ($ratio*$radius * cos(deg2rad($dir+$angle*$i + $angle/2)));
            $coordinates[] = $y + ($ratio*$radius * sin(deg2rad($dir+$angle*$i + $angle/2)));
        }
        return $coordinates;
    }

    /**
     * Create an smooth circle on image
     * Used instead of GD ImageFilledEllipse
     * 
     * See more info
     * https://www.php.net/manual/en/function.imageantialias.php
     */
    private function imageSmoothCircle( &$img, $cx, $cy, $cr, $color ) {
        $ir = $cr;
        $ix = 0;
        $iy = $ir;
        $ig = 2 * $ir - 3;
        $idgr = -6;
        $idgd = 4 * $ir - 10;
        $fill = imageColorExactAlpha( $img, $color[ 'R' ], $color[ 'G' ], $color[ 'B' ], 0 );
        imageLine( $img, $cx + $cr - 1, $cy, $cx, $cy, $fill );
        imageLine( $img, $cx - $cr + 1, $cy, $cx - 1, $cy, $fill );
        imageLine( $img, $cx, $cy + $cr - 1, $cx, $cy + 1, $fill );
        imageLine( $img, $cx, $cy - $cr + 1, $cx, $cy - 1, $fill );
        $draw = imageColorExactAlpha( $img, $color[ 'R' ], $color[ 'G' ], $color[ 'B' ], 42 );
        imageSetPixel( $img, $cx + $cr, $cy, $draw );
        imageSetPixel( $img, $cx - $cr, $cy, $draw );
        imageSetPixel( $img, $cx, $cy + $cr, $draw );
        imageSetPixel( $img, $cx, $cy - $cr, $draw );
        while ( $ix <= $iy - 2 ) {
                if ( $ig < 0 ) {
                        $ig += $idgd;
                        $idgd -= 8;
                        $iy--;
                } else {
                        $ig += $idgr;
                        $idgd -= 4;
                }
                $idgr -= 4;
                $ix++;
                imageLine( $img, $cx + $ix, $cy + $iy - 1, $cx + $ix, $cy + $ix, $fill );
                imageLine( $img, $cx + $ix, $cy - $iy + 1, $cx + $ix, $cy - $ix, $fill );
                imageLine( $img, $cx - $ix, $cy + $iy - 1, $cx - $ix, $cy + $ix, $fill );
                imageLine( $img, $cx - $ix, $cy - $iy + 1, $cx - $ix, $cy - $ix, $fill );
                imageLine( $img, $cx + $iy - 1, $cy + $ix, $cx + $ix, $cy + $ix, $fill );
                imageLine( $img, $cx + $iy - 1, $cy - $ix, $cx + $ix, $cy - $ix, $fill );
                imageLine( $img, $cx - $iy + 1, $cy + $ix, $cx - $ix, $cy + $ix, $fill );
                imageLine( $img, $cx - $iy + 1, $cy - $ix, $cx - $ix, $cy - $ix, $fill );
                $filled = 0;
                for ( $xx = $ix - 0.45; $xx < $ix + 0.5; $xx += 0.2 ) {
                        for ( $yy = $iy - 0.45; $yy < $iy + 0.5; $yy += 0.2 ) {
                                if ( sqrt( pow( $xx, 2 ) + pow( $yy, 2 ) ) < $cr ) $filled += 4;
                        }
                }
                $draw = imageColorExactAlpha( $img, $color[ 'R' ], $color[ 'G' ], $color[ 'B' ], ( 100 - $filled ) );
                imageSetPixel( $img, $cx + $ix, $cy + $iy, $draw );
                imageSetPixel( $img, $cx + $ix, $cy - $iy, $draw );
                imageSetPixel( $img, $cx - $ix, $cy + $iy, $draw );
                imageSetPixel( $img, $cx - $ix, $cy - $iy, $draw );
                imageSetPixel( $img, $cx + $iy, $cy + $ix, $draw );
                imageSetPixel( $img, $cx + $iy, $cy - $ix, $draw );
                imageSetPixel( $img, $cx - $iy, $cy + $ix, $draw );
                imageSetPixel( $img, $cx - $iy, $cy - $ix, $draw );
        }
    }
    
}

