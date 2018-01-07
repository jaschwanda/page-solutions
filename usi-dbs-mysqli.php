<?php // ------------------------------------------------------------------------------------------------------------------------ //

/*

Extends the standard mysqli database access functions. These classes provide the following enhancemenets:

1) Optionally combines the frequently used prepare(), bind_param(), execute(), store_result() and bind_result() methods into a
   single call.

2) Throws an exception if any of the above methods pitch an error, this allows you to wrap the DBS access code in a try/catch
   block without having to test for errors after each method call.

3) Offers the get_bound_statement() method that returns the SQL string that was generated from the prepare() method in case 
   inspection of the query string is required.

4) Shortens common error() messages.

*/

class USI_Dbs extends mysqli {

   const VERSION = '1.1.1 (2017-11-14)';

   const PREPARE_ERROR_1 = '; check the manual that corresponds to your MySQL server version for the right syntax to use';

   function __construct($options) {
      @ parent::mysqli($options['host'], $options['user'], $options['hash'], $options['name']);
      if ($this->connect_errno) throw new USI_Dbs_Exception(__METHOD__. ':connect:' . $this->connect_error);
   } // __construct();

   public function prepare_x($statement, $inputs = null, $outputs = null, $execute = true, $store = true) {
      $query = new USI_Dbs_Stmt($this, $statement);
      $status = $query->prepare($statement);
      if (!$status) throw new USI_Dbs_Exception(__METHOD__. ':' . str_replace(USI_dbs::PREPARE_ERROR_1, '', $this->error));
      if ($inputs) {
         $query->bind_param_x($inputs);
         if ($execute) {
            $query->execute_x();
            if ($store) $query->store_result_x();
         }
      }
      if ($outputs) {
         $query->bind_result_x($outputs);
      }
      return($query);
   } // prepare_x();
   
} // Class USI_Dbs;

class USI_Dbs_Exception extends Exception { } // Class USI_Dbs_Exception;

class USI_Dbs_Stmt extends mysqli_stmt {

   const VERSION = '1.1.1 (2017-11-14)';

   const BIND_ERROR_1 = "Number of elements in type definition string doesn't match number of bind variables";
   const BIND_ERROR_2 = "Number of variables doesn't match number of parameters in prepared statement";
   const BIND_ERROR_3 = "Number of bind variables doesn't match number of fields in prepared statement";

   public static $errstr = null; 

   private $args = null;
   private $statement = null;
   private $status = null;

   function __construct($dbs, $statement) {
      @ parent::__construct($dbs);
      $this->statement = $statement;
   } // __construct();

   function __destruct() {
      @ $this->close();
   } // __destruct();

   public function bind_param_x($args) {
      $this->args = $args;
      set_error_handler(array($this, 'error_handler'));
      $status = call_user_func_array(array(parent, 'bind_param'), $args);
      restore_error_handler();   
      if (!$status) {
         $this->shorten_error_string();
         throw new USI_Dbs_Exception(__METHOD__ . ':' . self::$errstr);
      }
      return($status);
   } // bind_param_x();

   public function bind_result_x($args) {
      set_error_handler(array($this, 'error_handler'));
      $status = call_user_func_array(array(parent, 'bind_result'), $args);
      restore_error_handler();   
      if (!$status) {
         $this->shorten_error_string();
         throw new USI_Dbs_Exception(__METHOD__ . ':' . self::$errstr);
      }
      return($status);
   } // bind_result_x();

   public static function error_handler($errno, $errstr, $errfile, $errline) {
      if (!(error_reporting() & $errno)) return;
      self::$errstr = $errstr;
   } // error_handler();

   public function execute_x($store = false) {
      $status = parent::execute();
      if (!$status) throw new USI_Dbs_Exception(__METHOD__ . ':' . $this->error);
      if ($store) $this->store_result_x();
      return($status);
   } // execute_x();

   public function fetch_x() {
      $status = parent::fetch();
      if (false === $status) throw new USI_Dbs_Exception(__METHOD__ . ':' . $this->error);
      return($status);
   } // fetch_x();

   public function get_bound_statement() {
      if (!$this->args) return('Statement not bound with bind_param_x() function.');
      $num_args = count($args = $this->args);
      $num_fields = count($fields = str_split($args[0]));
      if ($num_fields != ($num_args - 1)) return(self::BIND_PARAM_ERROR);
      $parameters = array();
      for ($ith = 0; $ith < $num_fields; $ith++) {
         switch ($fields[$ith]) {
         case 'b': case 's': $parameters[] = "'" . $args[$ith+1] . "'"; break;
         case 'd': case 'i': $parameters[] = $args[$ith+1]; break;
         }
      }
      $tokens = str_split($this->statement);
      $num_tokens = count($tokens);
      for ($ith = 0, $jth = 0, $sql = ''; $ith < $num_tokens; $ith++) {
         if ('?' === $tokens[$ith]) {
            if ($jth <= $num_fields) $sql .= $parameters[$jth];
            $jth++;
         } else {
            $sql .= $tokens[$ith];
         }
      }
      return((($jth != $num_fields) ? 'Wrong number of ? in ' : '') . $sql);
   } // get_bound_statement();

   public function get_status() {
      $sql = $this->get_bound_statement();
      return('ROWS:' . (('select' == strtolower(substr($sql, 0, 6))) ? $this->num_rows : $this->affected_rows) . ' SQL:' . $sql);
   } // get_status();

   private function shorten_error_string() {
      if (strpos(self::$errstr, self::BIND_ERROR_1)) {
         self::$errstr = self::BIND_ERROR_1;
      } else if (strpos(self::$errstr, self::BIND_ERROR_2)) {
         self::$errstr = self::BIND_ERROR_2;
      } else if (strpos(self::$errstr, self::BIND_ERROR_3)) {
         self::$errstr = self::BIND_ERROR_3;
      } 
   } // shorten_error_string();

   public function store_result_x() {
      $status = parent::store_result();
      if (!$status) throw new USI_Dbs_Exception(__METHOD__ . ':' . $this->error);
      return($status);
   } // store_result_x();

} // Class USI_Dbs_Stmt;

// --------------------------------------------------------------------------------------------------------------------------- // ?>