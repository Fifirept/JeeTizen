
<?php

class Utils{
	
	private static $resu;
	
	public static function decode($message_name, $msg)
	{

		echo '0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890' . PHP_EOL;
		echo '0         1         2         3         4         5         6         7         8         9' . PHP_EOL;
		print_r($msg);
		echo PHP_EOL;
		
		$chars = unpack('C*', $msg);
		self::$resu = array();
		foreach($chars as $char)
		{
			self::$resu[] = $char;
		}
	
		echo 'msg -length ' . $message_name . ' '  . strlen($msg) . PHP_EOL;
	
		echo 'resu -0 ' . self::$resu[0] . PHP_EOL;
		$offset = 1;
		self::decode_string($offset);
	
		$offset += self::$resu[$offset] + 2;
		self::decode_string($offset);
	
	
		$offset += 2;
		echo 'resu -' . $offset 	  . ' ' . self::$resu[$offset] . PHP_EOL;
	
		$offset += 1;
		echo 'resu -' . $offset 	  . ' ' . self::$resu[$offset] . PHP_EOL;
	
		$offset += 1;
		if($offset >= strlen($msg))
		{
			return;
		}
		if(self::$resu[$offset] != 0) {
			self::decode_string($offset);

			$offset += self::$resu[$offset] + 2;
			self::decode_string($offset);

			$offset += self::$resu[$offset] + 2;
			self::decode_string($offset);
		} else if(self::$resu[$offset] == 0) {
			echo 'resu -' . $offset 	  . ' ' . self::$resu[$offset] . PHP_EOL;
			$offset += 1;
			self::decode_string($offset);
		}
	}
	
	private static function decode_string($offset)
	{
		echo 'resu -' . $offset 	  . ' ' . self::$resu[$offset] . " ";
		echo 'resu -' . ($offset + 1) . ' ' . self::$resu[$offset+1] . "\ ";
		echo 'resu -' . ($offset + 2);
		echo '[' . self::$resu[$offset] . '] \'';
		$len = self::$resu[$offset];
		$offset += 2;
		for($i = 0; $i < $len; $i++)
		{
			echo chr(self::$resu[$offset+$i]);
		}
		echo "'" . PHP_EOL;
	}
	
}
?>
