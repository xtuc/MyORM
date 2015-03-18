<?php

namespace ORM\Exception;

/**
 * ORM Execption handler
 */
class Exception extends \Exception
{
	protected $message = 'Exception inconnue'; // message de l'exception
	private   $string;                        // __toString cache
	protected $code = 0;                      // code de l'exception défini par l'utilisateur
	protected $file;                          // nom du fichier source de l'exception
	protected $line;                          // ligne de la source de l'exception
	private   $trace;                         // backtrace
	private   $previous;                      // exception précédente (depuis PHP 5.3)
	
	function __construct()
	{
		$old_error_handler = set_error_handler(array($this,"error_handler"));
	}
	
	public static function error_handler($errno, $errstr, $errfile, $errline)
	{		
		@header("Content-Type:text/html");
		@header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		
		$path_parts = pathinfo($errfile);
    	$filename = $path_parts['filename'];
    	
        $title = "ORM exception";

        $html = sprintf('<h1>%s</h1>', $title);
        $html .= '<p>L\'application peut ne pas fonctionner en raison de l\'erreur suivante:</p>';
        
        $html .= "<h2>Details</h2>";
        
        if ($errno) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $errno);
        }
        if ($errstr) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', $errstr);
        }
        if ($errfile) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $errfile);
        }
        if ($errline) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $errline);
        }
        
        exit(sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html));
    
		/* Ne pas exécuter le gestionnaire interne de PHP */
    	return true;
	}
	
	/*final private function __clone();           // Inhibits cloning of exceptions.

	final public  function getMessage();        // message of exception
	final public  function getCode();           // code of exception
	final public  function getFile();           // source filename
	final public  function getLine();           // source line
	final public  function getTrace();          // an array of the backtrace()
	final public  function getPrevious();       // previous exception
	final public  function getTraceAsString();  // formatted string of trace

	// Overrideable
	public function __toString();               // formatted string for display*/
}