<?php

$fileinput=readline('Enter file content:');
file_put_contents('file.txt',$fileinput);
echo "File created successfully.";
//read
$content=file_get_contents('file.txt');
echo "\nFile content: $content";
