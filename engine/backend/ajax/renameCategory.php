<?php
define('sAuthFile', 'sGUI');
define('sConfigPath',"../../../");
include("../../backend/php/check.php");
$result = new checkLogin();
$result = $result->checkUser();
if ($result!="SUCCESS"){
echo "FAIL";
	die();
}
// *****************
?>
<?php
if ($result!="SUCCESS"){
	//echo $result;
	//header("location: auth.php");
	//die();
}

if ($_POST["newValue"] && $_POST["oldValue"] && $_POST["id"])
{
	$_POST["id"] = intval($_POST["id"]);
	$_POST["newValue"] = htmlspecialchars(html_entity_decode($_POST["newValue"]));
	$_POST["newValue"] = mysql_real_escape_string($_POST["newValue"]);
	$_POST["oldValue"] = mysql_real_escape_string($_POST["oldValue"]);
	
	if ($_POST["newValue"]!=$_POST["oldValue"]){
		$_POST["newValue"] = utf8_decode($_POST["newValue"]);
		$updateCategory = mysql_query("
		UPDATE s_categories SET description='{$_POST["newValue"]}'
		WHERE id={$_POST["id"]}
		");
	}
}
?>