<?php

namespace ogproxy;

class Proxy extends Action {
    public function execute() {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url( $_SERVER['REQUEST_URI'] ?? '',  PHP_URL_PATH );
        if ( !$path ) {
            $this->error( 'No request path' );
        }
        $basePath = parse_url( $this->getConfig( 'proxy-url' ), PHP_URL_PATH );
        if ( substr( $path, 0, strlen( $basePath ) ) !== $basePath ) {
            $this->error( 'Base path mismatch' );
        }
        $pathInfo = ltrim( substr( $path, strlen( $basePath ) ), '/' );
        $target = 'https://' . $pathInfo;
        $targetHost = parse_url( $target, PHP_URL_HOST );
        if ( !in_array( $targetHost, $this->getConfig( 'allowed-hosts' ) ) ) {
            $this->error( "Target host not allowed" );
        }
        $fetcher = new Fetcher(
            $this->getConfig( 'memcached-host' ),
            $this->getConfig( 'memcached-port' )
        );
        $response = $fetcher->fetch( $target, $url );
        $info = $response['info'];
        $body = $response['body'];
        if ( $info['http_code'] !== 200 ) {
            $this->error( "Target returned HTTP code {$info['http_code']}" );
        }
        if ( !isset( $info['content_type'] ) ) {
            $this->error( 'No content type' );
        }
        header( 'Content-Type: ' . $info['content_type'] );
        echo $body;
    }

    function error( $msg = 'Not found' ) {
        header( 'HTTP/1.1 404 Not Found' );
        header( 'Content-Type: text/plain' );
        header( 'X-Content-Options: nosniff' );
        echo $msg . "\n";
        exit;
    }
}
