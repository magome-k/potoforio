<?php
require('library.php');
$db = dbconnect();
if (isset($_GET['search'])) {
    if (empty($_GET['textbox'])) {
        $errormessage = '未入力';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="GET">
    <input type="text" name="textbox">
    <input type="submit" name="search" value="検索">
    </form>
</body>
</html>