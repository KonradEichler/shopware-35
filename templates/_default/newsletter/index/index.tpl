<html>
<head>
	<title>Newsletter</title>
	<style type="text/css">
		a:link, a:visited {
			color:#fff;
			text-decoration:none;
		}
		a:hover, a:active {
			color:#fff;
			text-decoration:none;
		}
		a:hover {
			color:#fff;
			text-decoration:none;
		}
		div#navi_unten a {
			color:#8c8c8c;
			font-size: 12px !important;
			text-decoration:none;
		}
	</style>
</head>

<body style="height:100%; font-family:Arial, Helvetica, sans-serif; padding:0; background-color:#f6f3ec;" background="#f6f3ec;margin:0;padding:0;" leftmargin="0" topmargin="0">

<table align="center" width="100%" border="0" cellspacing="25" cellpadding="0" style="color:#8c8c8c;">
<tr>
<td>
<table align="center" width="560" bgcolor="#ffffff" border="0" cellspacing="25" cellpadding="0" style="color:#8c8c8c; border:1px solid #c6c6c6;">
<tr>
<td>

{include file="newsletter/index/header.tpl"}

{foreach from=$sCampaign.containers item=sCampaignContainer}

{if $sCampaignContainer.type == "ctBanner"}
	{include file="newsletter/container/banner.tpl"}	
{elseif $sCampaignContainer.type == "ctText"}	
	{include file="newsletter/container/text.tpl"}	
{elseif $sCampaignContainer.type == "ctSuggest"}
	{include file="newsletter/container/suggest.tpl" sCampaignContainer=$sRecommendations}	
{else if $sCampaignContainer.type == "ctArticles"}
	{include file="newsletter/container/article.tpl"}   
{else if $sCampaignContainer.type == "ctLinks"}
	{include file="newsletter/container/link.tpl"}
{/if}

{/foreach}

{include file="newsletter/index/footer.tpl"}

<!--FOOTER-->	     
</tr>
</td>
</table>

<img src="{url module='backend' controller='newsletter' action='log' mailling=$sMailling.id mailaddress=$sUser.mailaddressID fullPath}" style="width:1px;height:1px">

</td>
</tr>
</table>
</body>
</html>
