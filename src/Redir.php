<?php

namespace ogproxy;

class Redir extends Action {
    public function execute() {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url( $_SERVER['REQUEST_URI'] ?? '',  PHP_URL_PATH );
        if ( !$path ) {
            $this->redirError( 'No request path' );
        }
        $basePath = parse_url( $this->getConfig( 'redir-url' ), PHP_URL_PATH );
        if ( substr( $path, 0, strlen( $basePath ) ) !== $basePath ) {
            $this->redirError( 'Base path mismatch' );
        }
        $pathInfo = ltrim( substr( $path, strlen( $basePath ) ), '/' );
        $target = 'https://' . $pathInfo;
        $targetHost = parse_url( $target, PHP_URL_HOST );
        if ( !in_array( $targetHost, $this->getConfig( 'allowed-hosts' ) ) ) {
            $this->redirError( "Target host not allowed" );
        }

        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '-';
        if ( stripos( $ua, 'facebot' ) !== false
            || stripos( $ua, 'facebook' ) !== false
        ) {
            $this->showMangledHtml( $target, $url );
        } else {
            $this->reallyRedirect( $target );
        }
    }

    private function showMangledHtml( $target, $referer ) {
        $fetcher = new Fetcher(
            $this->getConfig( 'memcached-host' ),
            $this->getConfig( 'memcached-port' )
        );
        $response = $fetcher->fetch( $target, $referer );
        $info = $response['info'];
        $body = $response['body'];
        if ( $info['http_code'] !== 200 ) {
            $this->redirError( "Target returned HTTP code {$info['http_code']}" );
        }
        $ct = $info['content_type'];
        $ct0 = trim( explode( ';', $ct )[0] );
        if ( strcasecmp( $ct0, 'text/html' ) !== 0 ) {
            $this->redirError( "Target returned unexpected content type" );
        }
        $doc = new \DOMDocument;
        if ( !$doc->loadHTML( '<?xml encoding="UTF-8">' . $body ) ) {
            $this->redirError( "Can't parse HTML" );
        }
        header( 'Content-Type: text/html; charset=utf-8' );
        echo "<!DOCTYPE html>\n";
        $xpath = new \DOMXPath( $doc );
        echo "<html lang=\"en\">\n";
        echo "<head>\n";
        $titles = $xpath->query( '//head/title' );
        foreach ( $titles as $title ) {
            echo "<title>" . htmlspecialchars( $title->textContent ) . "</title>\n";
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
                        '" content="' . htmlspecialchars( $this->mangle( $content ) ) . "\">\n";
                }
            }
        }
        echo "</head>\n";
        echo "<body></body>\n";
        echo "</html>\n";
    }

    private function mangle( $url ) {
        return preg_replace( '/^https?:\//', $this->getConfig( 'proxy-url' ), $url );
    }

    private function reallyRedirect( $target ) {
        header( 'Location: ' . $target );
    }

    private function redirError( $msg = 'Not found' ) {
        header( 'HTTP/1.1 404 Not Found' );
        header( 'Content-Type: text/plain' );
        header( 'X-Content-Options: nosniff' );
        echo $msg . "\n";
        exit;
    }
}
