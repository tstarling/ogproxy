<?php

namespace ogproxy;

require __DIR__ . '/../src/Fetcher.php';

function proxy() {
    $config = require __DIR__ . '/../config/config.php';

    $url = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url( $_SERVER['REQUEST_URI'] ?? '',  PHP_URL_PATH );
    if ( !$path ) {
        error( 'No request path' );
    }
    $basePath = parse_url( $config['proxy-url'], PHP_URL_PATH );
    if ( substr( $path, 0, strlen( $basePath ) ) !== $basePath ) {
        error( 'Base path mismatch' );
    }
    $pathInfo = ltrim( substr( $path, strlen( $basePath ) ), '/' );
    $target = 'https://' . $pathInfo;
    $targetHost = parse_url( $target, PHP_URL_HOST );
    if ( !in_array( $targetHost, $config['allowed-hosts'] ) ) {
        error( "Target host not allowed" );
    }
    $fetcher = new Fetcher;
    $response = $fetcher->fetch( $target, $url );
    $info = $response['info'];
    $body = $response['body'];
    if ( $info['http_code'] !== 200 ) {
        error( "Target returned HTTP code {$info['http_code']}" );
    }
    if ( !isset( $info['content_type'] ) ) {
        error( 'No content type' );
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

proxy();
