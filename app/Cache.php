<?php
/**
 *  brAWS - brAthena Webservice for Ragnarok Emulators
 *  Copyright (C) 2015  brAthena, CHLFZ
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Cache
{
    private static $ptr = null;

    public static function init()
    {

        if(!extension_loaded('memcache') || !BRAWS_MEMCACHE)
            return;

        $memcache = new Memcache;
        $memcache->addServer(BRAWS_MEMCACHE_SERVER, BRAWS_MEMCACHE_PORT);
        if(!$memcache->getStats())
        {
            $memcache = null;
            return;
        }

        self::$ptr = $memcache;
    }

    public static function get($index, $defaultValue)
    {
        if(is_null(self::$ptr) === true)
            return ((is_callable($defaultValue)) ? $defaultValue():$defaultValue);

        $cached = self::$ptr->get($index, false);

        if($cached === false)
        {
            $cached = ((is_callable($defaultValue)) ? $defaultValue():$defaultValue);
            self::$ptr->set($index, $cached, false, BRAWS_MEMCACHE_EXPIRE);
        }

        return $cached;
    }

    public static function delete($index)
    {
        if(is_null(self::$ptr) === true)
            return false;

        return self::$ptr->delete($index);
    }

    public static function flush()
    {
        if(is_null(self::$ptr) === true)
            return false;

        return self::$ptr->flush();
    }
}
