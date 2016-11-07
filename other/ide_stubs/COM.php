<?php
/**
 * Class COM
 *
 * This is just a stub class.
 */
class COM
{
    /**
     * This is just a dummy function to make IDE autocomplete less insane.
     *
     * @param int $bytes
     * @param int $dummy
     * @return string
     */
    public function GetRandom($bytes, $dummy)
    {
        static $fp = null;
        if (!$fp) {
            $fp = fopen('/dev/urandom', 'rb');
        }
        return fread($fp, $bytes);
    }
}

throw new Exception('Attempting to include IDE stub files in a project.');
