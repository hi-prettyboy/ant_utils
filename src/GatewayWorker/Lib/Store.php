<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Utils\GatewayWorker\Lib;
use Utils\GatewayWorker\Config;
/**
 * 存储类
 * 这里用memcache实现
 */
class Store
{
    /**
     * 实例数组
     * @var array
     */
    protected static $instance = array();

    /**
     * 获取实例
     * @param string $config_name
     * @throws \Exception
     */
    public static function instance($config_name)
    {
        // memcached 驱动
        if(Config\Store::$driver == Config\Store::DRIVER_MC)
        {
            if(!isset(Config\Store::$$config_name))
            {
                echo "Config\\Store::$config_name not set\n";
                throw new \Exception("Config\\Store::$config_name not set\n");
            }

            if(!isset(self::$instance[$config_name]))
            {
                if(extension_loaded('Memcached'))
                {
                    self::$instance[$config_name] = new \Memcached;
                }
                elseif(extension_loaded('Memcache'))
                {
                    self::$instance[$config_name] = new \Memcache;
                }
                else
                {
                    sleep(2);
                    exit("extension memcached is not installed\n");
                }
                foreach(Config\Store::$$config_name as $address)
                {
                    list($ip, $port) = explode(':', $address);
                    self::$instance[$config_name] ->addServer($ip, $port);
                }
            }
            return self::$instance[$config_name];
        }
        // redis 驱动
        elseif(Config\Store::$driver == Config\Store::DRIVER_REDIS)
        {
            if(!isset(Config\Store::$$config_name))
            {
                echo "Config\\Store::$config_name not set\n";
                throw new \Exception("Config\\Store::$config_name not set\n");
            }
            if(!isset(self::$instance[$config_name]))
            {
                ini_set('default_socket_timeout',-1);
                self::$instance[$config_name] = new \Utils\GatewayWorker\Lib\StoreDriver\Redis();
                $config = Config\Store::$$config_name;
                // 只选择第一个ip作为服务端
                $address = current($config);
                list($ip, $port) = explode(':', $address);
                $timeout = 1;
                self::$instance[$config_name]->connect($ip, $port, $timeout);
                if (Config\Store::$auth) {
                    self::$instance[$config_name]->auth(Config\Store::$auth);
                }
                self::$instance[$config_name]->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            }else{
                try{
                    self::$instance[$config_name]->ping();
                }catch (\RedisException $e){
                    self::$instance[$config_name] = new \Utils\GatewayWorker\Lib\StoreDriver\Redis();
                    $config = Config\Store::$$config_name;
                    // 只选择第一个ip作为服务端
                    $address = current($config);
                    list($ip, $port) = explode(':', $address);
                    $timeout = 1;
                    self::$instance[$config_name]->connect($ip, $port, $timeout);
                    self::$instance[$config_name]->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
                }
            }
            return self::$instance[$config_name];
        }
        // 文件驱动
        else
        {
            if(!isset(self::$instance[$config_name]))
            {
                self::$instance[$config_name] = new \Utils\GatewayWorker\Lib\StoreDriver\File($config_name);
            }
            return self::$instance[$config_name];
        }
    }
}
