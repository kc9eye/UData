REM Generate documents
REM ignoring the controller direcotries and wwwroot
php phpDocumentor.phar -d ..\etc\,..\lib,..\framework,..\models -t .\api 