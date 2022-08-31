<?php
/**
 * Created by PhpStorm.
 * Filename: test.php
 * User: Nguyễn Văn Ước
 * Date: 30/08/2022
 * Time: 06:02
 */

require './vendor/autoload.php';

$str = file_get_contents(__DIR__ . '/asset/text.txt');
$rake = new \Uocnv\Rake\Rake($str);
$keyWords = $rake->getKeyword(15);

$result = '';
foreach ($keyWords as $keyWord => $score) {
    $result .= $keyWord . ' ==> ' . $score . "\n\r";
}

file_put_contents(__DIR__ . '/asset/output.txt', $result);
