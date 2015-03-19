<?php 

namespace ORM;

class ORM {
	
	/**
	 * Version à deux caracteres sinon l'__autoloader ne marche pas
	 */
	const version = 1.5;
	
	function __construct() {
		if (version_compare(phpversion(), '5.3.0', '>=')) {
			spl_autoload_register(array(__CLASS__, 'autoload'), true, FALSE);
		} else {
			spl_autoload_register(array(__CLASS__, 'autoload'));
		}
	}

	public static function autoload($classname)
	{
		if(strstr($classname, "ORM\\"))
		{
			$classname = str_replace("ORM\\", "", $classname);
			
			if(file_exists(BLL . "$classname.php")) /* File exists dans le dossier BLL ? */
			{
				require BLL . "$classname.php";
			}
			elseif(file_exists(DAL . $classname . "_v". ORM::version .".php")) /* File exists dans le dossier DAL ? */
			{
				require DAL . $classname . "_v". ORM::version .".php";
			}
			else
			{
				$sql = new \sql();

				if(AUTOGENERATE && $sql->sql_table_exists($classname)) /* Check si il peut etre g�n�rer et siil existe */
				{
					$ORM = new ORM();
					$content = $ORM->classgenerator($classname);
					
					if(ALWAYSAUTOGENERATE)
					{
						$content = str_replace("<?php", "", $content);
						$content = str_replace("?>", "", $content);
					
						eval($content);
					}
					else 
					{
						if($ORM->saveclasstofile($classname ."_v" . ORM::version .".php", $content, DAL))
						{
							require(DAL . $classname ."_v". ORM::version .".php");
						}
					}
				}
			}
		}
	}
	
	public function saveclasstofile($filename,$filecontent,$directory)
	{
		if (!file_exists($directory))
		mkdir($directory);
		$handle = fopen($directory.$filename, "w");
		
		if(fwrite ( $handle , $filecontent ))
		{
			fclose($handle);
			
			return TRUE;
		}
		else 
		{
			fclose($handle);
			
			return FALSE;
		}
	}
	
	function classgenerator($table)
	{		
		global ${"".SQL};
		$sqlormconnect = ${"".SQL};

		$select = SQL;
		$save = SQL2;
		if (!isset($save))
			$save = SQL;
	
		$relation = RELATION;
		$cascade = CASCADE;
	
		$intotheset = INTOTHESET;
		$interface = array();	
		$parents_func = array();
		$class = $table;
		$keychar="";
		$thisvarinparent ="";
		$key = "";
		$childobject  ="";
		
		$table_relation_exists = ($sqlormconnect->sql_table_exists($relation)) ? TRUE : FALSE;
	
$c = "<?php
namespace ORM;

use ORM\Exception\DALException;
		
	/*
	*
	* -----------------------------------------------------------------------------------
	* ORM version : ". self::version ."
	* Class Name : $class
	* Generator : ORMGEN by PLATEL Renaud generated on ". gethostname() ."
	* Date Generated : ".date("d.m.Y H")."h
	* File name : $class.php
	* Table : ".$sqlormconnect->get_Database().".$table 
	* -----------------------------------------------------------------------------------
	*/
	
class $class extends Common
{
	
	// **********************
	// Variables
	// **********************
	";
	
		/**
		 * Generate variables
		 */
				$result = $sqlormconnect->sql_query("SHOW COLUMNS FROM  `".$table."`");
				while ($row = $sqlormconnect->sql_fetch_object($result))
				{
					$col = $row->Field;
					if (strpos($row->Type,"("))
					$type=substr($row->Type,0,strpos($row->Type,"("));
					else
					$type=$row->Type;
					if ($row->Null == "NO")
					$isnull = 0;
					else
					$isnull = 1;

					if($row->Key == "PRI")
					{
						$c.= "
	protected $$col; // PRI
	const $col = '$col'; // PRI
	const primary_key = '$col'; // PRI";
						if ($type!="int")
						$keychar =1;
						$key = $col;
						$isnull = 2;
					}
					else
					{
						$c.= "
	protected $$col;
	const $col = '$col';";
					}
					$interface[$col] = array($col, $type, $isnull, 0);
				}
	
	if ($relation!="")
	{
				$c.="
	
	// **********************
	// Parents object for this class (Keys)
	// **********************
	";
				/* Relations for relation table of your database or foreign keys */
				if ($table_relation_exists)
				{
					$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE TABLE_NAME = '".$table."'";
					$result = $sqlormconnect->sql_query($sqlorm);
					$i=0;
					
					while ($row = $sqlormconnect->sql_fetch_object($result))
					{					
						$parentvar="Parent".str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
						$interface[$parentvar] = array($parentvar, "ParentObject", 1, 0);
						$thisvarinparent[$i]=$row->COLUMN_NAME;
						$i++;
						$c.= "
	protected $$parentvar;";
					}
				}
				elseif ($relation=='FK')
				{
					$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '".$table."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."' AND REFERENCED_COLUMN_NAME IS NOT NULL";
	
					$result = $sqlormconnect->sql_query($sqlorm);
					$i=0;
					while ($row = $sqlormconnect->sql_fetch_object($result))
					{
						$parentvar="Parent".str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
						$interface[$parentvar] = array($parentvar, "ParentObject", 1, 0);
						$thisvarinparent[$i]=$row->COLUMN_NAME;
						$i++;
						$c.= "
	protected $".$parentvar.";";
					}
				}
	}		
	
	if ($relation!="")
	{
				$c.="
	
	// **********************
	// Childs array of object for this class (Foreign Keys)
	// **********************
	";
	
				/* Relations for relation table of your database or foreign keys */
				if ($table_relation_exists)
				{
					$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE REFERENCED_TABLE_NAME = '".$table."'";
					$result = $sqlormconnect->sql_query($sqlorm);
					
					while ($row = $sqlormconnect->sql_fetch_object($result))
					{
						$childvar=ucfirst($row->TABLE_NAME).str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
						$interface[$childvar] = array($childvar, "ChildObject", 1, 0);
						$c.= "
	protected $$childvar;";
					}
				}
				elseif ($relation=='FK')
				{
					$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."'";
					$result = $sqlormconnect->sql_query($sqlorm);
					while ($row = $sqlormconnect->sql_fetch_object($result))
					{
						$childvar=ucfirst($row->TABLE_NAME).str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
						$interface[$childvar] = array($childvar, "ChildObject", 1, 0);
						$c.= "
	protected $$childvar;";
					}
				}
	}
	
				$c.= "
	
	// **********************
	// Interface to control the variable of this class and the update flag
	// **********************
	
	protected \$Database; // Database for this object
	public \$isNew = 0; // Memory for insert
	protected \$isToSaveOrToUpdate = 0; // Memory for update
	//Memory array of fields for update
	protected \$structure = array(
		";
			foreach($interface as $colomName=>$colomArray)
			{
				// 0 : Nom du champ
				// 1 : type
				// 2 : Nullalbe
				// 3 : Is to save ?
				// 4 : Valeur
			$c .= "'$colomName' => array('". $colomArray[0] ."', '". $colomArray[1] ."', '". $colomArray[2] ."', '". $colomArray[3] ."', '')";
				
				if(end($interface) !== $colomArray)
				{
		$c .= ",
		";
				}
			}
	$c .= "
	);
	
	// **********************
	// Constructor
	// **********************
	function __construct (\$val = null, \$property = self::primary_key)
	{
		global $".$select.";
		if((isset(\$val))&&(trim(\$val)!=\"\"))
		{
			if(\$property == self::primary_key)
			{
				\$val = intval(\$val);
			}
			else
			{
				\$val = addslashes(\$val);
			}
			
			\$query = $".$select."->sql_query(\"SELECT * FROM `$class` WHERE `\$property` = \".parent::quote(\$this->structure[\$property],\$val) .\" LIMIT 1\");

			if($".$select."->sql_num_rows(\$query) != 0)
			{
				while(\$row = $".$select."->sql_fetch_object(\$query))
	            {
	                ";
				foreach($interface as $colomName=>$colomArray)
				{
					if($colomArray[1] != "ChildObject" && $colomArray[1] != "ParentObject")
					{
					$c .= "\$this->$colomName = \$row->$colomName;
					\$this->structure['$colomName'][4] = \$row->$colomName;
					";
					}
				}
		$c .= "
	            }
			}
			else
			{
				\$this->isNew=1;
				\$this->isToSaveOrToUpdate=1;
			}
		}

		if (is_null(\$this->{self::primary_key}))
        {
            \$this->isNew=1;
            \$this->isToSaveOrToUpdate=1;
        }
        else
        {
           \$this->structure[self::primary_key][4] = \$this->{self::primary_key};
        }
	}
	";
	
	$c.="
	
	// **********************
	// Generic get & set method for this class
	// **********************
	
	";
	
	$c.="public function __get( \$property )
	{
	    if ( is_callable( array(\$this,'get_'.(string)\$property) ) )
	    {
	        return call_user_func( array(\$this,'get_'.(string)\$property) );
	    }
	    else
	    {
	        throw new DALException(\"get for \$property doesn't exists\");
	    }
	}
	
	public function __set( \$property, \$val )
	{
		if ( is_callable( array(\$this,'set_'.(string)\$property) ) )
		{
			\$val = addslashes(\$val);

			if ( \$val != call_user_func( array(\$this,'get_'.(string)\$property) ) )
			{
				call_user_func( array(\$this,'set_'.(string)\$property), \$val );
				\$this->isToSaveOrToUpdate=1;
				\$this->structure[\$property][3]=1;
				\$this->structure[\$property][4] = \$val;";
				if (trim($intotheset)!="")
			$c.="
				".trim($intotheset);
			$c.="
			}
		}
		else
		{
			throw new DALException( \"set for \$property doesn't exists\");
		}
	}

	public function __isset(\$property = NULL)
    {
        if ( is_callable( array(\$this,'get_'.(string)\$property) ) ) {
	        \$return = call_user_func( array(\$this,'get_'.(string)\$property) );

	       	if(empty(\$return) || is_null(\$return))
		    {
		    	return FALSE;
		    }
		    else
		    {
		    	return TRUE;
		    }
	    }
	    else {
	        throw new DALException(\"get for \$property doesn't exists\");
	    }
    }

    public function __unset(\$property)
    {
        if ( is_callable( array(\$this,'set_'.(string)\$property) ) )
	        return call_user_func( array(\$this,'set_'.(string)\$property), NULL );
	    else
	        throw new DALException(\"set for \$property doesn't exists\");
    }
	
	// **********************
	// Specific get & set method for this class
	// **********************
	
	public function get_isNew()
	{
		return intval(\$this->isNew);
	}
					
	public function get_structure()
	{
		return \$this->structure;		
	}
	
	";
	
	
	$sqlorm="SHOW COLUMNS FROM `".$table."`";
	
	$result = $sqlormconnect->sql_query($sqlorm);
	while ($row = $sqlormconnect->sql_fetch_object($result))
	{
		$col=$row->Field;
		$c.="public function get_".$col."()
	{
		return stripslashes(\$this->".$col.");
	}
	
	";
	
			$c.="public function set_".$col."(\$valeur = null)
	{
		if(is_null(\$valeur))
		{
			\$this->$col = NULL;
		}
		else
		{
			";
			
			if(isset($interface[$col]))
			{
				switch($interface[$col][1])
				{
					case "int":
		$c .= "if(is_numeric(\$valeur) && \$valeur >= 0)
			{
				\$this->$col = intval(\$valeur);
			}
			else
			{
				throw new DALException('$class::$col must be a valid integer');
			}";
						break;
					case "varchar":
		$c .= "if(is_string(\$valeur))
			{
				\$this->$col = addslashes(\$valeur);
			}
			else
			{
				throw new DALException('$class::$col must be a valid string');
			}";
						break;
					default:
		$c .= "\$this->$col = addslashes(\$valeur);";
						break;
				}
			}
			
		$c .= "
		}
		";
	
			if (is_array($thisvarinparent))
			{
				if (in_array($col, $thisvarinparent))
				{
					$varname="Parent".str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $col )));
	
					$c.="
		if (trim($"."this->".$col.")=='')
		{
			$"."this->".$col." = null;
			$"."this->".$varname." = null;
		}
		
		if (!is_null($"."this->".$varname."))
			$"."this->get_".ucfirst($varname)."(1);";
	
				}
			}
			$c.="
		return TRUE;
	}
	
	";
	}
	
	if ($relation!="")
	{
	$c.="// **********************
	// Specific get & set method for parents objects
	// **********************
	
	";
	
	if ($table_relation_exists)
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE TABLE_NAME = '".$table."'";
	}
	else
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '".$table."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."' AND REFERENCED_COLUMN_NAME IS NOT NULL";
	}
	
	$result = $sqlormconnect->sql_query($sqlorm);
	
	if ($sqlormconnect->sql_num_rows($result) !== 0)
	{	
		while ($row = $sqlormconnect->sql_fetch_object($result))
		{
				$varname=str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
				$parenttable=$row->REFERENCED_TABLE_NAME;
				$parentcolumn=$row->REFERENCED_COLUMN_NAME;
				$childcolumn=$row->COLUMN_NAME;
				$childobject = $row->REFERENCED_TABLE_NAME;
				
			$parents_func[] = "get_Parent".ucfirst($varname)."";
			
	$c.= "public function get_Parent".ucfirst($varname)."(\$forced = 0)
	{
		
		\$this->Parent".$varname." = new $childobject($"."this->".$childcolumn.");

		return $"."this->Parent".$varname.";
	}
	
	public function set_Parent".ucfirst($varname)."($"."$childobject)
	{
		\$this->$childcolumn=$".$childobject."->".$parentcolumn.";
		\$this->structure[\"$childcolumn\"][3]=1;
		\$this->isToSaveOrToUpdate=1;
		\$this->Parent$varname=$$childobject;
		return ($".$childobject.");
	}
			
	";
		}
		}
	}
	
	if ($relation!="")
	{
	$c.="// **********************
	// Specific get & set method for childs objets
	// **********************
	";
	
	if ($table_relation_exists)
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."'";
	}
	else
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."'";
	}
	$result = $sqlormconnect->sql_query($sqlorm);

	if ($sqlormconnect->sql_num_rows($result)!=0)
	{
	while ($row = $sqlormconnect->sql_fetch_object($result))
	{

		$varname=ucfirst($row->TABLE_NAME).str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
		
		$childtable=$row->TABLE_NAME;
		$childobject=$row->TABLE_NAME;
		$childcolumn=$row->COLUMN_NAME;
	
		$col = $sqlormconnect->sql_primary_key($childtable);
		if (is_null($col))
		{
			$orderby="";
		}
		else
		{
			$orderby=" ORDER BY ".$col;
		}
	
		if ($keychar==1)
		{
			$querydelete = "\"DELETE FROM `$childtable` WHERE $childcolumn = '\".$"."this->$key".".\"'\"";
			$query="\"SELECT * FROM `$childtable` WHERE $childcolumn = '\".$"."this->$key".".\"'";
			if ($orderby!="") $query.=$orderby."\""; else $query.= "\"";
		}
		else
		{
			$query="\"SELECT * FROM `$childtable` WHERE $childcolumn = \".$"."this->$key";
			if ($orderby!="") $query.=".\"".$orderby."\"";
			$querydelete = "\"DELETE FROM `$childtable` WHERE $childcolumn = \".$"."this->$key";
		}
	
		$c.="
	public function get_".ucfirst($varname)."()
	{
		global $"."$select;
		
		if ((is_null($"."this->".$varname."))&&(!is_null($"."this->$key)))
		{
			$"."query=".$query.";
			$"."result=$".$select."->sql_query($"."query);
			while($"."row = $".$select."->sql_fetch_object(\$result,'ORM\\$childobject'))
			{
				$"."this->add_".ucfirst($varname)."($"."row);
			}
		}
		return ($"."this->".$varname.");
	}
	
	public function set_".ucfirst($varname)."($".$childobject."s)
	{
		if (count($"."this->".$varname.")==0)
			foreach ($".$childobject."s as $"."var)
				add_".ucfirst($varname)."($"."var);
		else
			throw new DALException( \"Can set ".$varname." cause actual ".$varname." is not empty\");
		return ($".$childobject."s);
	}
	
	public function add_".ucfirst($varname)."(".$childobject." $".$childobject.")
	{
		if ($".$childobject."->$childcolumn!=$"."this->$key)
			$".$childobject."->$childcolumn=$"."this->$key;
		$"."count=count($"."this->".$varname.");
		while (isset($"."this->".$varname."[$"."count]))
			$"."count++;
		$"."this->".$varname."[$"."count]=$".$childobject.";
	}
	
	public function remove_".ucfirst($varname)."(".$childobject." $"."remove".$childobject.")
	{
		foreach ($"."this->".ucfirst($varname)." as $"."var)
		{
			if ($"."remove".$childobject." == $"."var)
			{
				$"."var->delete();
				$"."this->".$childobject." = null;
			}
		}
	}
	
	protected function save_".ucfirst($varname)."($"."transaction = null)
	{
		foreach ($"."this->$varname as $"."$childobject)
		{
			if ($".$childobject."->$childcolumn!=$"."this->$key)
				$".$childobject."->$childcolumn=$"."this->$key;
			$".$childobject."->save($"."transaction);
		}
	}
	
	public function delete_".ucfirst($varname)."($"."transaction = null)
	{
		global $"."$save;
		if (isset($"."this->$varname))
			foreach ($"."this->$varname as $"."$childobject)
			{
				$".$childobject."->delete($"."transaction);
			}
		
		$"."query = ".$querydelete.";
		$".$save."->sql_query($"."query);
		if ($".$save."->sql_error())
		{
			$"."erreur=$".$save."->sql_error().\"<br>\".$"."query;
			if ($"."transaction==\"On\")
			{
				$".$save."->sql_rollbacktransaction();
			}
			throw new DALException($"."erreur);
		}
		else
			return $".$save."->sql_affected_rows();
		
		$"."this->".ucfirst($varname)."= null;
	}
	";
	}
	}
	}
	
	/* delete, cascade for childs arrays*/
	$c.="
	// **********************
	// DELETE
	// **********************
	
	public function delete($"."transaction = null)
	{
		global $"."$save;
		$"."thistransaction = \"Off\";
		$"."return = null;
		
		if ((isset($"."this->$key))&&($"."this->$key!=0))
		{
			if (is_null($"."transaction))
			{
				$"."thistransaction=\"On\";
				$"."transaction = \"On\";
				if ($".$save."->TransactionMode == 1)
					$".$save."->sql_starttransaction();
			}
	";
	
	if ($table_relation_exists)
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."'";
	}
	else
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."'";
	}
	$result = $sqlormconnect->sql_query($sqlorm);
	
	if ($sqlormconnect->sql_num_rows($result)>0)
	{
	while ($row = $sqlormconnect->sql_fetch_object($result))
	{
		$varname=ucfirst($row->TABLE_NAME).str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
		$childtable=$row->TABLE_NAME;
		$childobject= $row->TABLE_NAME;
		$childcolumn=$row->COLUMN_NAME;
	
		$c.="		if ((count($"."this->get_".$varname."())!=0)&&(CASCADE))
				$"."this->delete_".$varname."($"."transaction);
	";
	}
	}
	
	$c.= "		if (($"."transaction==\"On\"))
			{
				";
	
	$c .= "
				$"."query = parent::makequery('DELETE', $"."this->Database, '".$class."', $"."this->structure);
				$"."Result = $".$save."->sql_query($"."query);
				if ($".$save."->sql_error())
				{
					$"."erreur=$".$save."->sql_error().\"<br>\".$"."query;
					if ($".$save."->TransactionMode == 1)
					{
						$".$save."->sql_rollbacktransaction();
					}
					throw new DALException($"."erreur);
				}
				else
					$"."return = $".$save."->sql_affected_rows($"."Result);
			}
			if (($".$save."->TransactionMode == 1)&&($"."thistransaction==\"On\"))
			{
				$".$save."->sql_committransaction();
			}
			return $"."return;
		}
		foreach ($"."this->structure as $"."field)
			unset($"."this->$"."field[0]);	
	}
	";
	
	$c.="
	// **********************
	// SAVE (INSERT or UPDATE)
	// **********************
	
	public function save(\$transaction = null)
	{		
		global $"."$save;
		\$thistransaction = \"Off\";
		
		if ((is_null($"."this->".$key."))||($"."this->".$key."==0))
		{
			$"."this->isToSaveOrToUpdate=1;
			$"."this->isNew=1;
		}
		";
	/* Parents save if some changes */
	if ($table_relation_exists)
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE TABLE_NAME = '".$table."'";
	}
	else
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '".$table."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."' AND REFERENCED_COLUMN_NAME IS NOT NULL";
	}
	$result = $sqlormconnect->sql_query($sqlorm);
	if ($sqlormconnect->sql_num_rows($result)>0)
	{
	while ($row = $sqlormconnect->sql_fetch_object($result))
	{
		$varname=str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
		$parenttable=$row->REFERENCED_TABLE_NAME;
		$parentcolumn=$row->REFERENCED_COLUMN_NAME;
		$childcolumn=$row->COLUMN_NAME;
		$childobject = $row->REFERENCED_TABLE_NAME;
	
		$c.="
		if (!is_null($"."this->"."Parent".$varname."))
			$"."this->$childcolumn = $"."this->"."Parent".$varname."->save($"."transaction);	
		";
	}
	}
	$c.="
		if ($"."this->isToSaveOrToUpdate==1)
		{
	
			if ((isset($"."this->$key))&&($"."this->$key!=\"0\")&&($"."this->isNew!=1))
			{
				\$query = parent::makequery('UPDATE', $"."this->Database, '".$class."', \$this->structure);
				\$result=$".$save."->sql_query($"."query);
			}
			else
			{
				\$query = parent::makequery('INSERT', $"."this->Database, '".$class."', $"."this->structure);
				$".$save."->sql_query($"."query);
				\$this->$key=$".$save."->sql_insert_id();
				\$this->isNew=0;
			}
		}
	";
	
	if ($table_relation_exists)
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."'";
	}
	else
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."'";
	}
	$result = $sqlormconnect->sql_query($sqlorm);
	if ($sqlormconnect->sql_num_rows($result)>0)
	{
	while ($row = $sqlormconnect->sql_fetch_object($result))
	{
		$varname=ucfirst($row->TABLE_NAME).str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
		$childtable=$row->TABLE_NAME;
		$childobject= $row->TABLE_NAME;
		$childcolumn=$row->COLUMN_NAME;
	
		$c.="
		if (!is_null($"."this->".$varname."))
			$"."this->save_".$varname."($"."transaction);
		";
	
	}
	}
	
	$c.= "
		$"."this->isToSaveOrToUpdate=0;
		foreach ($"."this->structure as $"."field)
			if(isset($"."field[0]))
			{
				$"."this->structure[$"."field[0]][3]=0;
			}
	
		return $"."this->$key;
	}

	public function last_insert(\$property = self::primary_key)
	{
		global $"."$save;
	
		\$query = $".$save."->sql_query(\"SELECT \$property AS last FROM `$class` ORDER BY \".self::primary_key.\" DESC LIMIT 1\");
		\$last = $".$save."->sql_fetch_row(\$query);

		return \$last[\"last\"];
	}
	
	public function toString($"."var = 'first')
	{
		$"."this->LoadAllParents();
		$"."this->LoadAllChilds();
		$"."return = \"Object \".__CLASS__.\" (<br>\";
		foreach ($"."this->structure as $"."field)
		{
			if ( ($"."field[1] == 'ChildObject') && (!is_null($"."this->{"."$"."field[0]})) && ( ( $"."var == 'first' ) || ( $"."var == 'down' ) ) )
			{
				$"."return .= '\"'.$"."field[0].'\" =>';
				$"."return .= \" Array ( <br>\";
				$"."i=0;
				foreach ($"."this->{"."$"."field[0]} as &$"."childvar)
				{
					$"."return .= $"."childvar->toString('down');
					$"."return .= \",<br>\";
					$"."i++;
				}
				$"."return = substr($"."return, 0, -5);
				$"."return .= \"<br> ) <br>\";
			}
			else
			{
				if ( ($"."field[1] == 'ParentObject') && (!is_null($"."this->{"."$"."field[0]})) && ( $"."var == 'first' ) )
				{
					$"."return .= '\"'.$"."field[0].'\" => ';
					$"."return .= $"."this->{"."$"."field[0]}->toString('none');
					$"."return .= \"<br>\";
				}
				else
				{					
					if ($"."this->{"."$"."field[0]}==\"\")
					{
						if ($"."field[2]==1)
						{
							$"."return .= '\"'.$"."field[0].'\" => null<br>';
						}
						else
						{
							$"."return .= '\"'.$"."field[0].'\" => \"\"<br>';
						}
					}
					else
					{
						if ( ($"."field[1] != 'ParentObject') && ($"."field[1] != 'ChildObject') )
						{
							$"."return .= '\"'.$"."field[0].'\" => '.$"."this->{"."$"."field[0]}.'<br>';
						}
					}
				}
			}
		}
		$"."return = substr($"."return,0,-1);
		$"."return .= \")\";
		return $"."return;
	}
	
	public function get_toJson($"."var = 'first')
	{
		$"."this->LoadAllParents();
		$"."this->LoadAllChilds();
		$"."return = \"{\";
		foreach ($"."this->structure as $"."field)
		{
			if ( ($"."field[1] == 'ChildObject') && (!is_null($"."this->{"."$"."field[0]})) && ( ( $"."var == 'first' ) || ( $"."var == 'down' ) ) )
			{
				$"."return .= '\"'.$"."field[0].'\":';
				$"."return .= \"[\";
				$"."i=0;
				foreach ($"."this->{"."$"."field[0]} as &$"."childvar)
				{
					$"."return .= $"."childvar->get_toJson('down');
					$"."return .= \",\";
					$"."i++;
				}
				$"."return = substr($"."return, 0, -1);
				$"."return .= \"],\";
			}
			else
			{
				if ( ($"."field[1] == 'ParentObject') && (!is_null($"."this->{"."$"."field[0]})) && ( $"."var == 'first' ) )
				{
					$"."return .= '\"'.$"."field[0].'\":';
					$"."return .= $"."this->{"."$"."field[0]}->get_toJson('none');
					$"."return .= \",\";
				}
				else
				{					
					if ($"."this->{"."$"."field[0]}==\"\")
					{
						if ($"."field[2]==1)
						{
							$"."return .= '\"'.$"."field[0].'\":null,';
						}
						else
						{
							$"."return .= '\"'.$"."field[0].'\":\"\",';
						}
					}
					else
					{
						if ( ($"."field[1] != 'ParentObject') && ($"."field[1] != 'ChildObject') )
						{
							if (($"."field[1]!='timestamp')&&($"."field[1]!='date')&&($"."field[1]!='datetime')&&($"."field[1]!='char')&&($"."field[1]!='varchar')&&($"."field[1]!='tinyblob')&&($"."field[1]!='tinytext')&&($"."field[1]!='blob')&&($"."field[1]!='text')&&($"."field[1]!='mediumblob')&&($"."field[1]!='mediumtext')&&($"."field[1]!='longblob')&&($"."field[1]!='longtext')&&($"."field[1]!='time')&&($"."field[1]!='enum'))
							{
								$"."return .= '\"'.$"."field[0].'\":'.$"."this->{"."$"."field[0]}.',';
							}
							else
							{
								$"."return .= '\"'.$"."field[0].'\":\"'.$"."this->{"."$"."field[0]}.'\",';
							}
						}
					}
				}
			}
		}
		$"."return = substr($"."return,0,-1);
		$"."return .= \"}\";
		return $"."return;
	}
	
	public function __clone()
	{
		\$this->LoadAllChilds();
		\$this->$key = null;
		\$this->isToSaveOrToUpdate = 1;
		foreach ($"."this->structure as $"."field)
			if (($"."field[1] == 'ChildObject')&&(!is_null($"."this->{"."$"."field[0]})))
				foreach ($"."this->{"."$"."field[0]} as &$"."childvar)
					$"."childvar = clone $"."childvar;
	}
							
	public function LoadAllParents()
	{
		";
	if ($table_relation_exists)
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."'";
	}
	else
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."'";
	}
	$result = $sqlormconnect->sql_query($sqlorm);
	if ($sqlormconnect->sql_num_rows($result)>0)
	{
		while ($row = $sqlormconnect->sql_fetch_object($result))
		{
			$varname=ucfirst($row->TABLE_NAME).str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
			$childtable=$row->TABLE_NAME;
			$childobject= $row->TABLE_NAME;
			$childcolumn=$row->COLUMN_NAME;
	
			$c.="if (!is_null($"."this->".$varname."))
			$"."this->get_".$varname."();
		";
	
		}
	}
		
$c .= "
	}
	
	public function LoadAllChilds()
	{";
	if ($table_relation_exists)
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME ,REFERENCED_COLUMN_NAME FROM ".$relation." WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."'";
	}
	else
	{
		$sqlorm="SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = '".$table."' AND REFERENCED_COLUMN_NAME = '".$key."' AND TABLE_SCHEMA = '".$sqlormconnect->get_Database()."'";
	}
	$result = $sqlormconnect->sql_query($sqlorm);
	
	if ($sqlormconnect->sql_num_rows($result)>0)
	{
		while ($row = $sqlormconnect->sql_fetch_object($result))
		{
			$varname=ucfirst($row->TABLE_NAME).str_replace ( "Id" , "" , str_replace ( "id" , "" , str_replace ( "ID" , "" , $row->COLUMN_NAME )));
			$childtable=$row->TABLE_NAME;
			$childobject= $row->TABLE_NAME;
			$childcolumn=$row->COLUMN_NAME;
		
			$c.="
		\$this->get_".$varname."();";
		}
	}
	$c.="
	}
		
	//endofclass
}
	?>
	";
		return $c;
	}
	
}
?>