# PHPHP

[WIP] PHP written by PHP

## Install

```bash
$ composer global require tzmfreedom/phphp
```

## Usage

```bash
$ phphp < /path/to/file
```

## With Apache

1. Enable CGI

2. Put CGI Bridge code to your PATH


```bash
$ echo 'sed 1d $1 | php /path/to/phphp' > /usr/local/bin/phphp
$ chmod +x /usr/local/bin/phphp
```

3. Put your PHPHP code on CGI Directory

/usr/lib/cgi-bin/hello.cgi
```php
#!/bin/bash /usr/local/bin/phphp
Content-type: text/html

<?php

echo "Hello<br/>";

function hoge($i) { echo $i . "<br/>"; }

hoge("World");
?>
```
