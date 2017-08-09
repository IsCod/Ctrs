<?php
namespace Ctrs;

class Log {

    protected static $_log_path = '';
    protected static $_date_fmt    = 'Y-m-d';
    protected static $_enabled = TRUE;

    /**
     * Constructor
     */
    public function __construct()
    {
        self::$_log_path = __DIR__ . '/logs';
        if ( ! is_dir(self::$_log_path) OR ! is_really_writable(self::$_log_path))
        {
            self::$_enabled = FALSE;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Write Log File
     *
     * Generally this function will be called using the global log_message() function
     *
     * @param   string  log name
     * @param   string  the error message
     * @param   bool    srror code
     * @return  bool
     */
    public static function write($log_name = 'error', $msg = '', $code = 0)
    {
        if (self::$_enabled === FALSE)
        {
            return FALSE;
        }

        self::$_log_path = 'logs';

        if (!is_dir(self::$_log_path)) {
            mkdir(self::$_log_path);
        }

        $filepath = self::$_log_path.'/'. $log_name . '-' . date(self::$_date_fmt). '.log';

        if ( ! file_exists($filepath))
        {
            // mkdir($filepath);
            touch($filepath);
        }

        if ( ! $fp = @fopen($filepath, 'a'))
        {
            return FALSE;
        }

        $msg['code'] = $code;
        $message = "[" . date('Y-m-d H:i:s') . "] " . "message : " . json_encode($msg) . "\n";

        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, FILE_WRITE_MODE);
        return TRUE;
    }

}
