<?php
class CsvIterator implements Iterator
{
    const DEFAULT_DELIMITER = ';';
    const DEFAULT_LENGTH = 65535;

    /**
     * The CSV file handler.
     *
     * @var resource
     * @access private
     */
    private $_handler = null;

    /**
     * The delimiter of the CSV file.
     *
     * @var string
     * @access private
     */
    private $_delimiter = null;
    
    /**
     * The delimiter of the CSV file.
     *
     * @var string
     * @access private
     */
    private $_newline = "\r\n";
    
    /**
     * The delimiter of the CSV file.
     *
     * @var string
     * @access private
     */
    private $_fieldmark = "\"";

    /**
     * The dafs
     *
     * @var integer
     * @access private
     */
    private $_length = 65535;

    /**
     * The row counter.
     *
     * @var integer
     * @access private
     */
    private $_key = null;

    /**
     * The element that will be returned on each iteration.
     *
     * @var mixed
     * @access private
     */
    private $_current = null;
    
    /**
     * The element that will be returned on each iteration.
     *
     * @var mixed
     * @access private
     */
    private $_header = null;

    /**
     * This is the constructor. It try to open the CSV file.
     *
     * @access public
     * @param string $filename The fullpath of the CSV file.
     * @param string $delimiter The delimiter.
     * @param integer $length The amount of bytes to be read on each iteration.
     *
     * @throws Exception
     */
    public function __construct ($filename, $delimiter = self::DEFAULT_DELIMITER, $header = null)
    {
        if (($this->_handler = fopen($filename, 'r')) === false) {
            throw new Exception("The file '$filename' cannot be opened");
        }
        $this->_delimiter = $delimiter;
        if(empty($header))
        {
        	$this->_read();
        	$this->_header = $this->_current;
        }
        else
        {
        	$this->_header = $header;
        }
    }
    
    public function SetFieldmark($fieldmark)
    {
    	$this->_fieldmark = $fieldmark;
    }
    
    public function GetHeader()
    {
    	return $this->_header;
    }

    /**
     * This is the destructor. It close the CSV file.
     *
     * @access public
     */
    public function __destruct ()
    {
        fclose($this->_handler);
    }

    /**
     * This method move the file pointer to the next row.
     *
     * @access public
     */
    public function next ()
    {
        $this->_read();
        $this->_key += 1;
    }

    /**
     * This method reset the file handler.
     *
     * @access public
     */
    public function rewind ()
    {
        rewind($this->_handler);
        $this->_read();
        $this->_read();
        $this->_key = 1;
    }

    /**
     * This method returns the current row number.
     *
     * @access public
     */
    public function key ()
    {
        return $this->_key;
    }

    /**
     * This methods return the current CSV row data.
     *
     * @access public
     * @return array The row as an one-dimensional array
     */
    public function current ()
    {
    	$data = array();
    	foreach ($this->_header as $key=>$name)
    	{
    		$data[$name] = isset($this->_current[$key]) ? $this->_current[$key] : '';
    	}
        return $data;
    }

    /**
     * This method checks if the current row is readable.
     *
     * @access public
     * @return boolean If the current row is readable.
     */
    public function valid ()
    {
        return $this->_current !== false;
    }

    /**
     * This method read the next row of the CSV file.
     *
     * @access private
     */
    private function _read ()
    {
        //$this->_current = fgetcsv($this->_handler, $this->_length, $this->_delimiter);
        if(!$this->_handler||feof($this->_handler))
        {
        	$this->_current = false;
        	return;
        }
        $count = 0;
		$line = stream_get_line($this->_handler, $this->_length, $this->_newline);
		while((empty($this->_fieldmark)||($count = substr_count($line, $this->_fieldmark)) % 2 != 0)&&!feof($this->_handler))
		{
			$line .= $this->_newline.stream_get_line($this->_handler, $this->_length, $this->_newline);
		}
		if(empty($line))
		{
			$this->_current = false;
			return;
		}
		$line = explode($this->_delimiter,$line);
		if(empty($count))
		{
			$this->_current = $line;
			return;
		}
		$this->_current = array();
		$row = "";
		do
		{
			$row .= current($line);
			$count = substr_count($row, $this->_fieldmark);
			if ($count % 2 != 0) {
				$row .= ";";
				continue;
			} elseif ($count) {
				$this->_current[] = str_replace($this->_fieldmark.$this->_fieldmark,$this->_fieldmark,substr($row,1,-1));
			} else {
				$this->_current[] = $row;
			}
			$row = "";
		}
		while (next($line)!==false);
    }
}
?>