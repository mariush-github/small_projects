<?php

$password_length=16;
$password = array();
for ($i=0;$i<$password_length;$i++) $password[$i] = chr(0x00);

$limits = [
[1,3],	// special characters
[1,3],	// numbers
[1,3],	// uppercase
[1,64], // lowercase , max should be set to max password length
];

$characters = array();
// build the character tables:
// special chars: !"#$%&'():;<=>?@
$characters[0] = array(); 
for ($i=0x21;$i<=0x29;$i++) array_push($characters[0],chr($i)); // !"#$%&'()
for ($i=0x3A;$i<=0x40;$i++) array_push($characters[0],chr($i)); // :;<=>?@
// numbers: 0..9
$characters[1] = []; for ($i=0x30;$i<=0x39;$i++) array_push($characters[1],chr($i));
// uppercase
$characters[2] = []; for ($i=0x41;$i<=0x5A;$i++) array_push($characters[2],chr($i));
// lowercase
$characters[3] = []; for ($i=0x61;$i<=0x7A;$i++) array_push($characters[3],chr($i));

function get_empty_char_position() {
	global $password;
	global $password_length;
	// pick a random position in the array
	
	$char = chr(0xFF);
	$position = -1;
	while ($char!=chr(0x00)) {
		$position = mt_rand(0,$password_length-1);
		$char = $password[$position];
	}
	return $position;
}

$chars_assigned = 0;
for ($i=0;$i<4;$i++) {
	// how many characters from this type to pick?
	$count = mt_rand($limits[$i][0],$limits[$i][1]);
	echo "character set $i, count=$count \n";
	if ($count > ($password_length-$chars_assigned)) $count = $password_length-$chars_assigned;
	// if last character set, force to use the maximum unused
	if ($i==4) $count = $password_length-$chars_assigned;
	for ($j=0;$j<$count;$j++) {
		$pos = get_empty_char_position();
		echo "charset $i, char $j, at position $pos\n";
		$random = mt_rand(1,count($characters[$i]))-1;
		$password[$pos] = $characters[$i][$random];
		$chars_assigned++;
	}
}

echo 'Your password is: ';
for ($i=0;$i<$password_length;$i++) echo $password[$i];
?>
