<?php

namespace ogproxy;

require __DIR__ . '/../src/Fetcher.php';

function maybeRedirect() {
    $config = require __DIR__ . '/../config/config.php';

    $url = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url( $_SERVER['REQUEST_URI'] ?? '',  PHP_URL_PATH );
    if ( !$path ) {
        redirError( 'No request path' );
    }
    $basePath = parse_url( $config['redir-url'], PHP_URL_PATH );
    if ( substr( $path, 0, strlen( $basePath ) ) !== $basePath ) {
        redirError( 'Base path mismatch' );
    }
    $pathInfo = ltrim( substr( $path, strlen( $basePath ) ), '/' );
    $target = 'https://' . $pathInfo;
    $targetHost = parse_url( $target, PHP_URL_HOST );
    if ( !in_array( $targetHost, $config['allowed-hosts'] ) ) {
        redirError( "Target host not allowed" );
    }

    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '-';
    if ( stripos( $ua, 'facebot' ) !== false
        || stripos( $ua, 'facebook' ) !== false
    ) {
        showMangledHtml( $target, $url, $config );
    } else {
        reallyRedirect( $target, $config );
    }
}

function showMangledHtml( $target, $referer, $config ) {
    $fetcher = new Fetcher;
    $response = $fetcher->fetch( $target, $referer );
    $info = $response['info'];
    $body = $response['body'];
    if ( $info['http_code'] !== 200 ) {
        redirError( "Target returned HTTP code {$info['http_code']}" );
    }
    $ct = $info['content_type'];
    $ct0 = trim( explode( ';', $ct )[0] );
    if ( strcasecmp( $ct0, 'text/html' ) !== 0 ) {
        redirError( "Target returned unexpected content type" );
    }
    $doc = new \DOMDocument;
    if ( !$doc->loadHTML( $body ) ) {
        redirError( "Can't parse HTML" );
    }
    echo "<!DOCTYPE html\n";
    $xpath = new \DOMXPath( $doc );
    echo "<html lang=\"en\">\n";
    echo "<head>\n";
    $title = $xpath->evaluate( '//head/title[0]/child::text()' );
    if ( $title ) {
        echo "<title>" . htmlspecialchars( $title ) . "</title>\n";
    }
    $metas = $xpath->query( '//head/meta' );
    if ( $metas ) {
        foreach ( $metas as $meta ) {
            /** @var \DOMElement $meta */
            $name = $meta->getAttribute( 'name' );
            $content = $meta->getAttribute( 'content' );
            $property = $meta->getAttribute( 'property' );
            if ( in_array( $name, [
                'title',
                'description',
            ] ) ) {
                echo '<meta name="' . htmlspecialchars( $name ) .
                    '" content="' . htmlspecialchars( $content ) . "\">\n";
            } elseif ( in_array( $property, [
                'og:description',
                'og:title',
                'og:image:width',
                'og:image:height',
            ] ) ) {
                echo '<meta property="' . htmlspecialchars( $property ) .
                    '" content="' . htmlspecialchars( $content ) . "\">\n";
            } elseif ( in_array( $property, [
                'og:image'
            ] ) ) {
                echo '<meta property="' . htmlspecialchars( $property ) .
                    '" content="' . htmlspecialchars( mangle( $content, $config ) ) . "\">\n";
            }
        }
    }
    echo "</head>\n";
    echo "<body></body>\n";
    echo "</html>\n";
}

function mangle( $url, $config ) {
    return preg_replace( '/^https?:\//', $config['proxy-url'], $url );
}

function reallyRedirect( $target, $config ) {
    header( 'Location: ' . $target );
}

function redirError( $msg = 'Not found' ) {
    header( 'HTTP/1.1 404 Not Found' );
    header( 'Content-Type: text/plain' );
    header( 'X-Content-Options: nosniff' );
    echo $msg . "\n";
    exit;
}

maybeRedirect();
