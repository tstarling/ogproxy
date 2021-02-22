<?php

namespace ogproxy;

class Fetcher {
    private $host;
    private $port;

    public function __construct( $host, $port ) {
        $this->host = $host;
        $this->port = $port;
    }

    public function fetch( $url, $referer ) {
        $memcached = new \Memcached;
        $memcached->addServer( $this->host, $this->port );
        $key = "ogproxy:fetch:" . md5( $url );
        $entry = $memcached->get( $key );
        if ( $entry === false ) {
            $c = curl_init( $url );
            curl_setopt_array( $c, [
                CURLOPT_REFERER => $referer,
                CURLOPT_USERAGENT => 'ogproxy@tstarling.com',
                CURLOPT_RETURNTRANSFER => true
            ] );
            $body = curl_exec( $c );
            $info = curl_getinfo( $c );
            $entry = [
                'body' => $body,
                'info' => $info
            ];
            $memcached->set( $key, $entry );
        }
        return $entry;
    }
}
