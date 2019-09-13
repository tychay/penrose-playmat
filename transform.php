<?php

$x_min = 2000;
$y_min = 2000;
$x_max = 0;
$y_max = 0;
$a_max = 0 - pow(10,10);
$a_min = pow(10,10);
function ColorHSLToRGB($h, $s, $l){
        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0){
              $m;
              $sv;
              $sextant;
              $fract;
              $vsf;
              $mid1;
              $mid2;

              $m = $l + $l - $v;
              $sv = ($v - $m ) / $v;
              $h *= 6.0;
              $sextant = floor($h);
              $fract = $h - $sextant;
              $vsf = $v * $sv * $fract;
              $mid1 = $m + $vsf;
              $mid2 = $v - $vsf;

              switch ($sextant)
              {
                    case 0:
                          $r = $v;
                          $g = $mid1;
                          $b = $m;
                          break;
                    case 1:
                          $r = $mid2;
                          $g = $v;
                          $b = $m;
                          break;
                    case 2:
                          $r = $m;
                          $g = $v;
                          $b = $mid1;
                          break;
                    case 3:
                          $r = $m;
                          $g = $mid2;
                          $b = $v;
                          break;
                    case 4:
                          $r = $mid1;
                          $g = $m;
                          $b = $v;
                          break;
                    case 5:
                          $r = $v;
                          $g = $m;
                          $b = $mid2;
                          break;
              }
        }
        return array( $r * 255.0, $g * 255.0, $b * 255.0);
}
/**
 * Get its position to the left and down relative to the bounding box.
 * 
 * In this case, the bounding box is approximately  1440x840
 * @param  string $coords A list of points spit back
 * @return array two numbers (roughly) from (0,0) to (1,1) representing the
 * position of ther center in the box
 */
function xy_ratio_in_box($coords) {
	global $x_min, $y_min, $x_max, $y_max;
	$coords = explode(' ', $coords);
	$x_sum = 0;
	$y_sum = 0;
	for ($i=0; $i<count($coords); $i+=2) {
		$x = $coords[$i];
		$y = $coords[$i+1];
		$x_sum += $x;
		$y_sum += $y;
		if ( $x < $x_min ) { $x_min = $x; }
		if ( $x > $x_max ) { $x_max = $x; }
		if ( $y < $y_min ) { $y_min = $y; }
		if ( $y > $y_max ) { $y_max = $y; }
	}
	$x_mid = $x_sum * 2/count($coords);
	$y_mid = $y_sum * 2/count($coords);
	//printf("midpoint: %.4f , %.4f\n", $x_sum * 2/count($coords), $y_sum * 2/count($coords));

	// TODO: Determine color from coordinates
	// first: position as a ratio of the bounding box
	$x_pos = $x_mid / 1440;
	$y_pos = $y_mid / 840;
	//printf("pos_1: %.3f , %.3f\n", $x_pos, $y_pos);
	
	return array($x_pos, $y_pos);
}
/**
 * Generate an RGB color from both its relative position in the box AND its
 * current RGB level (0-255).
 * 
 * @param  float $x_pos   x coord from 0 to 1
 * @param  float $y_pos   y coord from 0 to 1
 * @param  int $grayscale the grayscale value
 * @return array an RGB color
 */
function rgb_from_pos_scale($x_pos, $y_pos, $grayscale) {
	global $a_min, $a_max;
	// Put the mid point in 0,0 and compute position relative to it.
	$x_arc = $x_pos - 0.5;
	$y_arc = $y_pos - 0.5;
	//printf("pos_m: %.3f,%.3f\n", $x_arc, $y_arc);

	// hue is the angle (in degrees), (atan2 is -pi to pi)
	$hue = rad2deg(atan2($y_arc, $x_arc) + M_PI);
	//printf("hue: %.1f•\n", $hue);

	$sat = 100;
	// saturation should be the original color (in grayscale), mapped to 80-100
	$sat = 80 + $grayscale/255*20;
	$sat = 90 + $grayscale/255*10;

	// compute distance from middle
	$dist  = sqrt(pow($x_arc,2) + pow($y_arc,2)); // sqrt(.5) is the corners
	$dist2 = 2 * (pow($x_arc,2) + pow($y_arc,2)); // 0-1
	$dist3 = sqrt(2) * $dist; //0-1
	//printf("lnd: %.3f\n", log($dist3, 2));
	$log = log($dist3, 2);
	if ( $a_min > $log ) { $a_min = $log; }
	if ( $a_max < $log ) { $a_max = $log; }
	// min max: -4.9977 , -0.0605
	$dist4 = (4.9977 + $log) / 5;

	//printf("dist: %.3f\n", $dist);
	// brightness is lower further from center
	//$bri = 100 - (100 * $dist);
	// use a power scale for brightness changes
	//$bri = 100 - (77 * pow($dist3,1));
	//$bri = 100 - (60 * pow($dist4, 3));
	//$bri = 115 - (100 * $dist) - (30 * $grayscale/255);
	// tweak it
	//$bri = 100 - (75 * pow($dist2,.5)) - (25 * $grayscale/255);
	// try different ratios and allowing the color to lighten
	// old final version
	//$bri = 100 - (95 * pow($dist4,4)) - (15 * ($grayscale-127)/255);
	// give some bleed line room
	$bri = 100 - (110 * pow($dist4,4)) - (15 * ($grayscale-127)/255);
	if ($bri > 100) { $bri = 100; }
	
	//printf("bri: %.1f\n", $bri);
	//printf("hsl: %.1f , %.1f, %.1f\n", $hue, $sat, $bri);
	$hue /= 360;
	$sat /= 100;
	$bri /= 100;
	$rgb = ColorHSLToRGB($hue, $sat, $bri);
	$e_bri = $bri - 0.2*$bri;
	$e_sat = pow(1-$bri,.25);
	$rgbe = ColorHSLToRGB($hue, $e_sat, $e_bri);

	return array_merge($rgb, $rgbe);
}

/**
 * Replace dart with new color
 * 
 * @param  array $matches Here are the values
 * - 0: entire <polygon>
 * - 1: points coordinates in decimal notation
 * - 2: remnants
 * - 3,4,5: RGB of fill
 * - 6: remnants
 * - 7,8,9: RGB of stroke
 * - 11: 
 * - 10: stroke width
 * - 11: remnants
 * @return [type]          [description]
 */
function replace_dart($matches) {
	//print_r($matches);
	// Determine coordinates of points
	// $matches[1] has all the points in xy values.
	list ($x_pos, $y_pos) = xy_ratio_in_box($matches[1]);

	list ($red, $green, $blue, $redge, $gedge, $bedge) = rgb_from_pos_scale($x_pos, $y_pos, $matches[3]);
	//print_r(rgb_from_pos_scale($x_pos, $y_pos, $matches[3]));
	//printf("rgb: %.1f , %.1f, %.1f\n", $red, $green, $blue);
	//printf("rgb- %.1f , %.1f, %.1f\n", $redge, $gedge, $bedge);

	//echo '<polygon points="' . $matches[1] .'" type="dart"' . $matches[2] .'fill: rgb(100, 100, 100' . $matches[6] .'stroke: rgb('.$matches[7].', '.$matches[8].', '.$matches[9].'); stroke-width: '.$matches[10].'px'.$matches[11].'/>';
	return '<polygon points="' . $matches[1] .'" type="dart"' . $matches[2] .'fill: rgb(' . $red.', ' . $green . ', ' . $blue . $matches[6] .'stroke: rgb('.$redge.', '.$gedge.', '.$bedge.'); stroke-width: 1px'.$matches[11].'/>';
}
function replace_kite($matches) {
	//print_r($matches);
	list ($x_pos, $y_pos) = xy_ratio_in_box($matches[1]);

	// try swapping positions of x and y
	//list ($red, $green, $blue) = rgb_from_pos_scale($y_pos, $x_pos, $matches[3]);
	// try inverting grayscale for darts
	list ($red, $green, $blue, $redge, $gedge, $bedge) = rgb_from_pos_scale($x_pos, $y_pos, 255-$matches[3]);
	return '<polygon points="' . $matches[1] .'" type="kite"' . $matches[2] .'fill: rgb(' . $red.', ' . $green . ', ' . $blue . $matches[6] .'stroke: rgb('.$redge.', '.$gedge.', '.$bedge.'); stroke-width: 1px'.$matches[11].'/>';
}

// read the original file
$image = file_get_contents('penrose_orig.svg');

// TODO regex
// <polygon points="209.062 566.5 256.272 532.2 303.482 566.5" type="dart" id="1-1-1-1-1-1-1-1" class="dart color8" style="fill: rgb(117, 13, 29); stroke: rgb(221, 221, 221); stroke-width: 0.8px;"/>
$image = preg_replace_callback('/<polygon points="([^"]+)" type="dart"(.*?)fill: rgb\((\d+), (\d+), (\d+)(.*?)stroke: rgb\((\d+), (\d+), (\d+)\); stroke-width: (.*?)px(.*?)\/>/', 'replace_dart', $image);
$image = preg_replace_callback('/<polygon points="([^"]+)" type="kite"(.*?)fill: rgb\((\d+), (\d+), (\d+)(.*?)stroke: rgb\((\d+), (\d+), (\d+)\); stroke-width: (.*?)px(.*?)\/>/', 'replace_kite', $image);

// DATA:
printf("top left: %.4f , %.4f\n", $x_min, $y_min);
printf("bottom right: %.4f , %.4f\n", $x_max, $y_max);
printf("min max: %.4f , %.4f\n", $a_min, $a_max);
// top left: -19.2948 , -26.0682
// bottom right: 1448.7600 , 863.0680
// desired: 1440 x 840 was desired


// write the file
file_put_contents('penrose_new.svg', $image);