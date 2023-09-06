<?php
session_start();
require('../library.php');
// 書き直しで戻ってきた時のif文
if (isset($_GET['action']) && $_GET['action'] === 'rewrite' && isset($_SESSION['form'])){
    $form = $_SESSION['form'];
} else {
    //配列の初期化
        //初めて呼び出したときにエラーが出ないように
    $form = [
        'name' => '',
        'email' => '',
        'password' => '',
    ];
}
$error = [];

//formが送信された時にチェックするようにする
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    // 入力が空の場合のチェック
    if ($form['name'] === '') {
        // エラーが起こった事を記録させ別な場所に表示させるために変数に入れる
        $error['name'] = 'blank';
    }
    $form['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    if ($form['email'] === '') {
        $error['email'] = 'blank';
    } else {
        // メールアドレス重複確認
        $db = dbconnect();
        // 入力されたアドレスがサーバーにあるか確認する
        $stmt = $db->prepare('select count(*) from members where email=?');
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param('s', $form['email']);
        $success = $stmt->execute();
        if (!$success) {
            die ($db->error);
        }
        // 確認した結果を代入する
        $stmt->bind_result($cnt);
        $stmt->fetch();
        // count(*)の中が0ならなし、1ならあり
        if ($cnt > 0) {
            $error['email'] = 'duplicate';
        }
    }
    $form['password'] = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    if ($form['password'] === '') {
        $error['password'] = 'blank';
        // 文字列の長さを判定
    } else if (strlen($form['password']) < 4) {
        $error['password'] = 'length';
    }
    //画像のチェック
    $image = $_FILES['image'];
    // ファイルの名前を受け取る
    if ($image['name'] !== '' && $image['error'] === 0) {
        // ファイル形式を確認するファンクション  tmp_nameはphpが一時的に付ける名前
        //$type = mime_content_type($image['tmp_name']);は非推奨
        $finfo = new finfo();
        $type = $finfo->file($image['tmp_name'], FILEINFO_MIME_TYPE);
        // ファイル形式を指定する
        if ($type !== 'image/png' && $type !== 'image/jpeg') {
            $error['image'] = 'type';
        }
    }
    // エラーがない場合にページを移動する
    if (empty($error)) {
        // $form[]を配列にしてsessionに入力された情報を入れておく
        $_SESSION['form'] = $form;
        // 画像のアップロード
        // 名前に日付を入れて画像の簡易的な重複チェック
        if ($image['name'] !== '') {
            $filename = date('YmdHis') . '_' . $image['name'];
            if (!move_uploaded_file($image['tmp_name'], '../member_img/' . $filename)) {
                die('ファイルのアップロードに失敗しました');
            }
            $_SESSION['form']['image'] = $filename;
        } else {
            $_SESSION['form']['image'] = '';
        }

        header('Location: check.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>会員登録</title>

    <link rel="stylesheet" href="../style.css"/>
</head>

<body>
<div id="wrap">
    <div id="head">
        <h1>会員登録</h1>
    </div>

    <div id="content">
        <p>次のフォームに必要事項をご記入ください。</p>
        <form action="" method="post" enctype="multipart/form-data">
            <dl>
                <dt>ニックネーム<span class="required">必須</span></dt>
                <dd>
                    <!-- 入力された情報が正しい場合次のページに行ってもvalueで値を保持させる -->
                    <input type="text" name="name" size="35" maxlength="255" value="<?php echo h($form['name']); ?>"/>
                    <?php //表示、非表示を制御
                    if (isset($error['name']) && $error['name'] === 'blank'): ?>
                        <p class="error">* ニックネームを入力してください</p>
                    <?php endif; ?>
                </dd>
                <dt>メールアドレス<span class="required">必須</span></dt>
                <dd>
                    <input type="text" name="email" size="35" maxlength="255" value="<?php echo h($form['email']); ?>"/>
                    <?php if (isset($error['email']) && $error['email'] === 'blank'): ?>
                        <p class="error">* メールアドレスを入力してください</p>
                    <?php endif; ?>
                    <?php if (isset($error['email']) && $error['email'] === 'duplicate'): ?>
                    <p class="error">* 指定されたメールアドレスはすでに登録されています</p>
                    <?php endif; ?>
                <dt>パスワード<span class="required">必須</span></dt>
                <dd>
                    <input type="password" name="password" size="10" maxlength="20" value="<?php echo h($form['password']); ?>"/>
                    <?php if (isset($error['password']) && $error['password'] === 'blank'): ?>
                        <p class="error">* パスワードを入力してください</p>
                    <?php endif; ?>
                    <?php if (isset($error['password']) && $error['password'] === 'length'): ?>
                        <p class="error">* パスワードは4文字以上で入力してください</p>
                    <?php endif; ?>
                </dd>
                <dt>写真など</dt>
                <dd>
                    <input type="file" name="image" size="35" value=""/>
                    <?php if (isset($error['image']) && $error['image'] === 'type'): ?>
                        <p class="error">* 写真などは「.png」または「.jpg」の画像を指定してください</p>
                    <?php endif; ?>
                        <p class="error">* 恐れ入りますが、画像を改めて指定してください</p>
                </dd>
            </dl>
            <div><input type="submit" value="入力内容を確認する"/></div>
        </form>
    </div>
</body>

</html>