<?php

namespace SuperGiggle;

class Os
{

    
    /**
     * Is the current platform windows?
     *
     * @return boolean
     */
    public function isWindows()
    {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }


}
