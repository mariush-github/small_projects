<?php

// reset dictionary every time it's full
$option_reset_when_full = false;


$zFile = new zWriter(__DIR__ .'/output.txt.z');
$dict = new clsDictionary();

$input = file_get_contents(__DIR__.'/input.txt');

$input_len = strlen($input);
$s = '';
$i=-1;
$continue = true;
while ($continue==true) {
	$i++;
	if ($i==$input_len) {
		$continue=false;
	} else {
		$s .= substr($input,$i,1);
	}
	if (($continue==false) && ($s!='')) { // end of file, still have some data
		$id = $dict->findSymbol($s);
		if ($id==-1) { // ex "abcd" -> write symbol for "abc", then write symbol for d
			$first_id = $dict->findSymbol(substr($s,0,strlen($s)-1));
			$zFile->addValue($first_id,$dict->num_bits);
			$second_id = $dict->findSymbol(substr($s,strlen($s)-1,1));
			$zFile->addValue($second_id,$dict->num_bits);
		} else {
			$zFile->addValue($id,$dict->num_bits);
		}
	} else {
		$id = $dict->findSymbol($s);
		if ($id==-1) { // ex "ab" -> write symbol for "a", then add "ab" to dictionary
			$first_id = $dict->findSymbol(substr($s,0,strlen($s)-1));
			$zFile->addValue($first_id,$dict->num_bits);
			$dict->addSymbol($s);
			// experiment - reset dictionary every time we hit 16 bit symbol lengths
			if (($option_reset_when_full==true) && ($dict->nextId==65536)) {
				$zFile->addValue(256,16); // output the reset dictionary symbol, on 16 bits
				$dict->reset();
			}
			$s = substr($s,strlen($s)-1,1);
		}
	}
}
$zFile->flush();
$zFile->close();


class clsDictionary {
	public $num_bits = 9;
	public $max_bits = 16;
	public $nextId = 257;
	
	private $symbols;
	private $map; 
	
	function __construct() {
		$this->reset();
	}
	function __destruct() {
	}
	
	function reset() {
		$this->symbols = [];
		$this->map = [];
		for ($i=0;$i<256;$i++) {
			$this->symbols[$i]= chr($i);
			$this->map[chr($i)] = $i;
		}
		$this->nextId = 257;
		$this->num_bits = 9;
		$this->max_bits = 16;
	}
	
	function findSymbol($symbol) {
		if (isset($this->map[$symbol])==FALSE) return -1;
		return $this->map[$symbol];
	}
	function addSymbol($symbol) {
		if ($this->nextId == 65536) return -1;
		$this->symbols[$this->nextId] = $symbol;
		$this->map[$symbol] = $this->nextId;
		$this->nextId++;
		if ($this->nextId<65536) $this->num_bits = 16;
		if ($this->nextId<32769) $this->num_bits = 15;
		if ($this->nextId<16385) $this->num_bits = 14;
		if ($this->nextId<8193) $this->num_bits = 13;
		if ($this->nextId<4097) $this->num_bits = 12;
		if ($this->nextId<2049) $this->num_bits = 11;
		if ($this->nextId<1025) $this->num_bits = 10;
		if ($this->nextId<513) $this->num_bits = 9;
		return ($this->nextId-1);
	}
}

class zWriter {
	public $filename; 
	private $handle; 
	private $bits;
	private $bitsCnt;
	public $buffer;
	private $bufferLen;
	
	function __construct($filename='') {
		$this->handle = FALSE;
		$this->bits = '';
		$this->bitsCnt = 0;
		if ($filename!='') return $this->create($filename,true);
		return FALSE;
	}
	function create($filename,$header=true) {
		if ($this->handle!==FALSE) $this->close();
		$this->handle = fopen($filename,'w');
		if (($this->handle!==FALSE) && ($header==true)) {
			fwrite($this->handle, chr(0x1f).chr(0x9d).chr(0x90));
			$this->buffer = '';
			$this->bufferLen = 0;
		}
		return $this->handle;
	}
	function close() {
		if ($this->handle!==FALSE) {
			fclose($this->handle);
			$this->handle = FALSE;
		}
	}
	function addBit($bit) {
		$this->bits .= $bit;
		$this->bitsCnt++;
		if ($this->bitsCnt>7) {
			$byte = substr($this->bits,0,8);
			$this->bits = substr($this->bits,8);
			$this->bitsCnt = $this->bitsCnt - 8;
			$byte_rev = strrev($byte);
			$char = chr(base_convert($byte_rev,2,10));
			$this->buffer .= $char;
			$this->bufferLen++;
			if (($this->bufferLen==512) && ($this->handle!==FALSE)) { 
				fwrite($this->handle,$this->buffer,512);
				$this->bufferLen=0;
				$this->buffer = '';
			}
		}
	}
	function addValue($value, $count=9) {
		//echo "addvalue $value $count \n";
		$v = $value & 0x0000FFFF;
		for ($i=0;$i<$count;$i++) {
			$bit = $v & 1;
			$this->addBit($bit);
			$v = intdiv($v,2);
		}
	}
	function flush() {
		$pad = 8-$this->bitsCnt;
		if ($pad!=8) {
			for ($i=0;$i<$pad;$i++) {
				$this->addBit('0');
			}
		}
		if ($this->bufferLen!=0) {
			fwrite($this->handle,$this->buffer,$this->bufferLen);
			$this->bufferLen=0;
			$this->buffer = '';
		}
	}
	function __destruct() {
		$this->close();
	}
	
}


?>