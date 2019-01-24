<?php
require_once('phpQuery-onefile.php');

//FeedWordPressプラグインのCustom Post Settingsで設定したカスタムフィールドから値を取得
$lnk = get_post_meta($post->ID , 'url' ,true);
$lnk_rj = mb_strstr($lnk, '.html',true);

$rj_number = "";
$url = "";
// 自分のアフィリエイトIDをここに設定する。
$affiId = "XXXXXXX";

// 同人か商業かでURLを変更
if(strpos($lnk_rj,'RJ')!==false){
	// リンクに'RJ'が含まれる場合(同人)。
	$rj_number = mb_strstr($lnk_rj, 'RJ');
	$url = 'https://www.dlsite.com/maniax/work/=/product_id/'.$rj_number.'.html'; // 対象のURL
}else if(strpos($lnk_rj,'BJ')!==false){
	// リンクに'BJ'が含まれる場合(商業コミック)。
	$rj_number = mb_strstr($lnk_rj, 'BJ');
	$url = 'https://www.dlsite.com/books/work/=/product_id/'.$rj_number.'.html'; // 対象のURL
}
$affi_link = "http://www.dlsite.com/home/dlaf/=/link/work/aid/".$affiId."/id/".$rj_number.".html";

// URLからhtml取得(cURLを利用する)
$data = "";
$curlDlsite = curl_init();
curl_setopt($curlDlsite, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curlDlsite, CURLOPT_URL, $url);
curl_setopt($curlDlsite, CURLOPT_TIMEOUT, 60);
$data = curl_exec($curlDlsite);
curl_close($curlDlsite);

// htmlをエンコーディング
$html = mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8');

/**
 * phpQueryを使ってスクレイピング
 */
$doc = phpQuery::newDocument($html);
// 商品の説明文を取得 
$story = $doc[".work_story"]->text();
// 商品の画像URLを取得
$src = $doc[".slider_item"]->find("img");

// 商品の画像や説明文をpタグで囲って変数に格納。
// ここは記事に載せたい情報を自分の好きなように整形する。
$bimg = "<p>".$src."</p>";
$tagged_story = '<p class="tagged-story">'.$story.'</p>';

/**
 * URL短縮を行う。
 * 使用するAPIは「is.gd」。
 * https://is.gd/apishorteningreference.php
 */
// 元のURL(短縮前)
$before_url = $affi_link;
$curlIsgd = curl_init();
// 取得対象URLを設定
curl_setopt($curlIsgd, CURLOPT_URL , 'http://is.gd/create.php?format=simple&format=json&url=' . rawurlencode($before_url));
// ヘッダー取得
curl_setopt($curlIsgd, CURLOPT_HEADER, 1 );						
// 証明書の検証を実施しない
curl_setopt($curlIsgd, CURLOPT_SSL_VERIFYPEER, false );			
// curl_exec()の結果を文字列で返す
curl_setopt($curlIsgd, CURLOPT_RETURNTRANSFER, true );			
// タイムアウトの秒数
curl_setopt($curlIsgd, CURLOPT_TIMEOUT, 15 );						
// リダイレクト先追跡有無
curl_setopt($curlIsgd, CURLOPT_FOLLOWLOCATION , true );			
// 追跡回数
curl_setopt($curlIsgd, CURLOPT_MAXREDIRS, 5 );

$isgdData = curl_exec($curlIsgd);
$isgdInfo = curl_getinfo($curlIsgd);
curl_close($curlIsgd);
	
// 取得したデータ(json)
$json = substr($isgdData, $isgdInfo['header_size']) ;
// レスポンスヘッダー
$rspheader = substr($isgdData, 0, $isgdInfo['header_size']);
// 取得したjsonをオブジェクトに変換
$obj = json_decode($json);
if(isset($obj->shorturl) && !empty( $obj->shorturl)){
    // 取得した短縮URL
    $shorten_url = $obj->shorturl;
}
