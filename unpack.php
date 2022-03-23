<?php
//
//  Unpacks .packed files.  Run php.exe unpack.php after editing the file name and output folder.
//
//  Edit php.ini option memory_limit and set a high value otherwise script may crash when extracting
// big files because the script is basic and tries to read the whole file in memory. 
//
// example memory_limit=1000MB to allow the script to use up to 1000 MB of RAM.
//
// -- YOU CAN EDIT THESE -- 

$filename = 'c:/temp/Music.packed';

$output = 'c:/temp/unpack/';

// -- DO NOT EDIT BELOW --

$h = fopen($filename,'r');


function ctv($s) {
	$value = ord($s[3])* 16777216;
	$value += ord($s[2])*65536;
	$value += + ord($s[1])*256;
	$value += ord($s[0]);
	return $value;
}

$filelist = [];

$header = fread($h,4);
$reserved = fread($h,4);
$filecount = fread($h,4);  $filecount = ctv($filecount);
for ($i=0;$i<$filecount;$i++) {
	$data = fread($h,4); $v = ctv($data); //echo $v." ";
	$data = fread($h,$v); $path = $data;
	$data = fread($h,4); $size = ctv($data); //echo $v." ";
	$data = fread($h,4); $offset = ctv($data); //echo $v." ";
	echo str_pad($offset,8,' ', STR_PAD_LEFT).' : '.str_pad($size,8,' ', STR_PAD_LEFT).' : '.$path."\n";
	array_push($filelist,[$path,$size,$offset]);
}
foreach ($filelist as $i => $fileinfo) {
	echo "extracting ".$fileinfo[0]."...";
	$result = fseek($h,$fileinfo[2]);
	$buffer = fread($h,$fileinfo[1]);
	$destination = $output .'/'.$fileinfo[0]; 
	$destination = str_replace('//','/',$destination);
	$destination = str_replace('\\','/',$destination);
	$path_parts = pathinfo($destination);
	
	$result = @mkdir($path_parts['dirname'],0777,true);
	$result = file_put_contents($destination,$buffer);
	echo " done.\n";
	
}
fclose($h);
?>
