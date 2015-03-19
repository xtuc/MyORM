<?php

namespace ORM;

class Common
{	
	private $SQLRead;
	private $SQLWrite;
	
	function __construct() {

	}

	protected function sql($database = NULL)
	{
		switch ($database) {
			case "write" :
				if (empty($this->SQLWrite))
				{
					$this->SQLWrite = new \sql(\sql::write);
				}
				return $this->SQLWrite;
			break;
			default:
				if (empty($this->SQLRead))
				{
					$this->SQLRead = new \sql(\sql::read);
				}

				return $this->SQLRead;
			break;
		}		
	}
	
	protected function makequery($type, $database, $class, $structure)
	{	
		//trouver la PK
		foreach ($structure as $thiskey => $field)
		{
			if (isset($field[4]))
			{
				if (isset($field[2]) && $field[2] == 2)
				{
					$Key = $thiskey;
					if (($field[1]!='timestamp')&&($field[1]!='date')&&($field[1]!='datetime')&&($field[1]!='char')&&($field[1]!='varchar')&&($field[1]!='tinyblob')&&($field[1]!='tinytext')&&($field[1]!='blob')&&($field[1]!='text')&&($field[1]!='mediumblob')&&($field[1]!='mediumtext')&&($field[1]!='longblob')&&($field[1]!='longtext')&&($field[1]!='time')&&($field[1]!='enum'))
						$KeyValue = $field[4];
					else
						$KeyValue = "'".$field[4]."'";
				}
			}
		}
		
		if ($type=='SELECT')
		{
			$fields = "";
			foreach ($structure as $field)
				if (($field[1] != 'ChildObject')&&($field[1] != 'ParentObject'))
				{
					$fields.= "`$field[0]` ,";
				}
			$fields = substr($fields,0,strlen($fields)-2);
			
			$query = "SELECT ".$fields." FROM `".$database."`.`".$class."` WHERE ".$Key." = ".$KeyValue;
		}
		
		if ($type=='INSERT')
		{
			$fields = "";
			$values = "";
			foreach ($structure as $field)
			if (isset($field[1]) && ($field[1] != 'ChildObject')&&($field[1] != 'ParentObject'))
			{
				$fields.= "`$field[0]` ,";
	
				if ((($field[2]==1)||($field[2]==2))&&((is_null($field[4]))||($field[4]=='')))
					$values .= "null, ";
				else
				{
					if (((is_null($field[4]))||($field[4]==''))&&(($field[1]=='tinyint')||($field[1]=='smallint')||($field[1]=='mediumint')||($field[1]=='int')||($field[1]=='decimal')||($field[1]=='float')||($field[1]=='double')||($field[1]=='bit')||($field[1]=='bool')||($field[1]=='serial')))
						$field[4]=0;
					if (($field[1]!='timestamp')&&($field[1]!='date')&&($field[1]!='datetime')&&($field[1]!='char')&&($field[1]!='varchar')&&($field[1]!='tinyblob')&&($field[1]!='tinytext')&&($field[1]!='blob')&&($field[1]!='text')&&($field[1]!='mediumblob')&&($field[1]!='mediumtext')&&($field[1]!='longblob')&&($field[1]!='longtext')&&($field[1]!='time')&&($field[1]!='enum'))
						$values .= $field[4].", ";
					else
						$values .= "'".$field[4]."', ";
				}
			}
			$fields = substr($fields,0,strlen($fields)-2);
			$values = substr($values,0,strlen($values)-2);
			
			$query = "INSERT INTO `".$database."`.`".$class."` ( ".$fields." ) VALUES ( ".$values." )";
		}
		
		if ($type=='UPDATE')
		{
			$fieldsup = "";
			foreach ($structure as $field)
				if (isset($field[1]) && ($field[1] != 'ChildObject')&&($field[1] != 'ParentObject')&&($field[3] == '1'))
				{
					if ((($field[2]==1)||($field[2]==2))&&((is_null($field[4]))||($field[4]=='')))
						$fieldsup.= "`$field[0]` = null, ";
					else
					{
						if (((is_null($field[4]))||($field[4]==''))&&(($field[1]=='tinyint')||($field[1]=='smallint')||($field[1]=='mediumint')||($field[1]=='int')||($field[1]=='decimal')||($field[1]=='float')||($field[1]=='double')||($field[1]=='bit')||($field[1]=='bool')||($field[1]=='serial')))
							$field[4]=0;
						if (($field[1]!='timestamp')&&($field[1]!='date')&&($field[1]!='datetime')&&($field[1]!='char')&&($field[1]!='varchar')&&($field[1]!='tinyblob')&&($field[1]!='tinytext')&&($field[1]!='blob')&&($field[1]!='text')&&($field[1]!='mediumblob')&&($field[1]!='mediumtext')&&($field[1]!='longblob')&&($field[1]!='longtext')&&($field[1]!='time')&&($field[1]!='enum'))
							$fieldsup.= "`$field[0]` = ".$field[4].", ";
						else
							$fieldsup.= "`$field[0]` = '".$field[4]."', ";
					}
				}
			$fieldsup = substr($fieldsup,0,strlen($fieldsup)-2);

			$query = "UPDATE `".$database."`.`".$class."` SET ".$fieldsup." WHERE ".$Key." = ".$KeyValue;
		}
		
		if ($type=='DELETE')
		{
			$query = "DELETE FROM `".$database."`.`".$class."` WHERE ".$Key." = ".$KeyValue;
		}
		
		
		return $query;
	}
		
	public function dump($pre = FALSE)
	{
		if($pre)
		{
			echo "<pre>";
		}
		
		print_r($this);
		
		if($pre)
		{
			echo "</pre>";
		}
	}
}