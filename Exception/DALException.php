<?php

namespace ORM\Exception;

/**
 * ORM Execption handler
 */
class DALException extends Exception
{
	protected $message;  // exception message
	private   $string;                          // __toString cache
	protected $code = 0;                        // user defined exception code
	protected $file;                            // source filename of exception
	protected $line;                            // source line of exception
	private   $trace;                           // backtrace
	private   $previous;                        // previous exception if nested exception

	public function __construct($message = "Unknown exception", $code = 0, $previous = NULL)
	{
		$this->message = $message;
		$this->code = $code;
		$this->previous = $previous;
		
		header("Content-Type:text/html");
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		
		echo $this->renderBody();
		exit();
	}
	
	protected function renderBody()
    {
    	$path_parts = pathinfo($this->file);
    	$filename = $path_parts['filename'];
    	
        $title = "ORM\\$filename exception";

        $trace = str_replace(array('#', '\n'), array('<div>#', '</div>'), $this->trace);
        $html = sprintf('<h1>%s</h1>', $title);
        $html .= '<p>L\'application peut ne pas fonctionner en raison de l\'erreur suivante:</p>';
        
        $html .= "<h2>Details</h2>";
        
        if ($this->code) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $this->code);
        }
        if ($this->message) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', $this->message);
        }
        if ($this->file) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $this->file);
        }
        if ($this->line) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $this->line);
        }
        
        $html .= '<h2>Trace</h2>';
        
        foreach($this->getTrace() as $key=>$trace)
        {
        	$file = (isset($trace["file"])) ? $trace["file"] : "{main}";
        	$class = (isset($trace["class"])) ? $trace["class"]."->" : "";
        	$fonction = (isset($trace["fonction"])) ? $trace["fonction"] : "__construct";
        	$args = (isset($trace["args"])) ? str_replace("[","",str_replace("]","",json_encode($trace["args"]))) : "";
        	
        	$html .= "#" . $key . " <b>". $file ."</b>: " . $class . "" . $fonction ."(<i>". $args ."</i>) <br />";
        }

        return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html);
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