<?php
/**
 * ondrejd/php-utils
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @license Mozilla Public License 2.0 https://www.mozilla.org/MPL/2.0/
 * @link https://github.com/ondrejd/php-utils
 */

namespace ondrejd\Utils;

/**
 * Class for importing CSV files.
 *
 * Usage:
 * <pre>
 * $import = CsvImport::import('file.csv');
 * if ($import->success()) {
 *   echo 'Read '.count($import).' records.'.PHP_EOL;
 *   foreach($import as $row) {
 *     echo join(',', $row).PHP_EOL;
 *   }
 * }
 * </pre>
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 */
class CsvImport implements \ArrayAccess, \Countable {
  const SEP_COMMA = ',';
  const SEP_SEMICOL = ';';
  const SEP_VLINE = '|';

  const QUOTE_SINGLE = "'";
  const QUOTE_DOUBLE = '"';
  const QUOTE_AUTO = 'auto';

  /**
   * @var string $filename
   */
  protected $filename;

  /**
   * @var boolean $header
   */
  protected $header = false;

  /**
   * @var string $separator
   */
  protected $separator = self::SEP_COMMA;

  /**
   * @var string $quotes
   */
  protected $quotes = self::QUOTE_AUTO;

  /**
   * @var string $success
   */
  protected $success = false;

  /**
   * @var string $data
   */
  protected $data = array();

  /**
   * @var integer $columns
   */
  protected $columns = 0;

  /**
   * @param array $settings
   * @return void
   * @throws \Exception Whenever given settins are not valid.
   */
  private function __construct($settings) {
    $this->header = $settings['header'];
    $this->separator = $settings['separator'];
    $this->quotes = $settings['quotes'];
    $this->filename = $settings['filename'];
  } // end __construct($settings)

  /**
   * @param string $file
   * @param string $sep (Optional.)
   * @param string $quote (Optional.)
   * @param boolean $header (Optional.)
   * @return \CsvImport
   */
  public static function import(
    $file,
    $sep = self::SEP_COMMA,
    $quote = self::QUOTE_AUTO,
    $header = false
  ) {
    $self = new self(array(
        'filename' => $file,
        'separator' => $sep,
        'quotes' => $quote,
        'header' => $header
    ));

    return $self->process();
  } // end import($file, $sep, $quote, $header)

  /**
   * @return \CsvImport
   * @throws \Exception Whenever CSV file doesn't exist or is not readable.
   */
  private function process() {
    if (!file_exists($this->filename) || !is_readable($this->filename)) {
      throw new \Exception('CSV file "'.$this->filename.'" does not exist or is not readable!');
    }

    $handle = @fopen($this->filename, 'r');

    if ($handle) {
      $this->parse($handle);
    }

    $this->success = true;

    return $this;
  } // end process()

  /**
   * @param resource $handle
   * @return void
   */
  private function parse($handle) {
    $i = 0;
    while (($buffer = fgets($handle, 4096)) !== false) {
      if ($i == 0 && $this->header === true) {
        $i++;
        continue;
      }

      $row = $this->parseRow($buffer);
      if (count($row) > 0) {
        $this->data[] = $this->parseRow($buffer);
      }
    }
  } // end parse()

  /**
   * @param string $buffer
   * @return array
   */
  private function parseRow($buffer) {
    $buffer = trim($buffer);

    if (empty($buffer)) {
      return array();
    }

    $data = explode($this->separator, $buffer);

    if (count($data) == 0) {
      return array();
    }

    $row = array();
    $complete = '';
    $splitted = false;
    $last_of_split = false;
    $split_expected_end = null;

    foreach ($data as $col) {
      $len = strlen($col);

      if (strpos($col, self::QUOTE_SINGLE) === 0 && $col[$len-1] != self::QUOTE_SINGLE) {
        $splitted = true;
        $split_expected_end = self::QUOTE_SINGLE;
      } else if (strpos($col, self::QUOTE_DOUBLE) === 0 && $col[$len-1] != self::QUOTE_DOUBLE) {
        $splitted = true;
        $split_expected_end = self::QUOTE_DOUBLE;
      } else if ($splitted && strpos($col, $split_expected_end) === $len - 1) {
         $last_of_split = true;
      }

      switch ($this->quotes) {
        case self::QUOTE_SINGLE:
          $val = trim($col, self::QUOTE_SINGLE);
          break;

        case self::QUOTE_DOUBLE:
          $val = trim($col, self::QUOTE_DOUBLE);
          break;

        case self::QUOTE_AUTO:
        default:
          if (strpos($col, self::QUOTE_SINGLE) !== false) {
            $val = trim($col, self::QUOTE_SINGLE);
          } else if (strpos($col, self::QUOTE_DOUBLE) !== false) {
            $val = trim($col, self::QUOTE_DOUBLE);
          } else {
            $val = $col;
          }
          break;
      }

      if ($splitted && !$last_of_split) {
        $complete = empty($complete) ? $complete . $val : $complete . ',' . $val;
      } else if ($splitted && $last_of_split) {
        $complete = empty($complete) ? $complete . $val : $complete . ',' . $val;
        $row[] = $complete;
        $complete = '';
        $splitted = false;
        $last_of_split = false;
      } else {
        $row[] = $val;
      }
    }

    if (count($row) > $this->columns) {
      $this->columns = count($row);
    }

    return $row;
  } // end parseRow($buffer)

  /**
   * @return string
   */
  public function getFilename() {
    return $this->filename;
  } // end getFilename()

  /**
   * @return integer
   */
  public function getColumnsCount() {
    return $this->columns;
  } // end getColumnsCount()

  /**
   * @return string
   */
  public function getSeparator() {
    return $this->separator;
  } // end getSeparator()

  /**
   * @return string
   */
  public function getQuotes() {
    return $this->quotes;
  } // end getQuotes()

  /**
   * @return boolean
   */
  public function hasHeader() {
    return $this->header;
  } // end hasHeader()

  /**
   * @return boolean
   */
  public function success() {
    return $this->success;
  } // end success()

  /**
   * @param mixed $offset
   * @return boolean
   * @see \ArrayAccess
   */
  public function offsetExists($offset) {
    return isset($this->data[$offset]);
  } // end offsetExists($offset)

  /**
   * @param mixed $offset
   * @return mixed
   * @see \ArrayAccess
   */
  public function offsetGet($offset) {
    if ($this->offsetExists($offset)) {
      return $this->data[$offset];
    }

    return null;
  } // end offsetGet($offset)

  /**
   * @param mixed $offset
   * @param mixed $value
   * @return void
   * @see \ArrayAccess
   */
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->value[] = $value;
    } else {
      $this->value[$offset] = $value;
    }
  } // end offsetSet($offset, $value)

  /**
   * @param mixed $offset
   * @return void
   * @see \ArrayAccess
   */
  public function offsetUnset($offset) {
    if ($this->offsetExists($offset)) {
      unset($this->data[$offset]);
    }
  } // end offsetUnset($offset)

  /**
   * @return integer
   * @see \Countable
   */
  public function count() {
    return count($this->data);
  } // end count()
} // End of CsvImport
