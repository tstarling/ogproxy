<?php

namespace ogproxy;

class Index extends Action {
    public function execute() {
        $url = $_GET['url'] ?? '';
        if ( $url ) {
            $host = parse_url( $url, PHP_URL_HOST );
            if ( !in_array( $host, $this->config['allowed-hosts'] ) ) {
                $this->showForm( "Host \"$host\" is not on the list of allowed hosts" );
                return;
            }
            if ( !preg_match( '!^https://(.*)$!', $url, $m ) ) {
                $this->showForm( "Only HTTPS links are supported" );
                return;
            }
            $target = $this->getConfig( 'redir-url' ) . '/' . $m[1];
            $this->showSuccess( $target );
        } else {
            $this->showForm();
        }
    }

    private function showHead() {
        header( 'Content-Type: text/html; charset=utf-8' );
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>News linker</title>
    <style>
        .error {
            font-weight: bold;
            color: #c00;
        }
        .url-input {
            width: 100%;
        }
    </style>
</head>
<?php
    }

    private function showForm( $error = '' ) {
        $this->showHead();
        ?>
<body class="form">
<h1>News linker</h1>
<?php
    if ( $error ) {
        echo "<div class=\"error\">" . htmlspecialchars( $error ) . "</div>\n";
    }
?>
<form>
<p><label>Enter URL: <input class="url-input" type="text" name="url"></label></p>
<p><input type="submit" name="submit" value="Make link"></p>
</form>
</body>
</html>
<?php
    }

    private function showSuccess( $url ) {
        $this->showHead();
        $encUrl = htmlspecialchars( $url );
        ?>
<body class="success">
<div>
    Your URL is
    <div class="url">
        <a href="<?=$encUrl?>"><?=$encUrl?></a>
    </div>
</div>
</body>
</html>
<?php
    }
}
