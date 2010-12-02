{extends file='frontend/index/index.tpl'}

{* Breadcrumb *}
{block name='frontend_index_start' prepend}
	{assign var='sBreadcrumb' value=[['name'=>'Paypal Express Order Pending Page'|snippet:'PalpalPendingTitle']]}
{/block}

{block name='frontend_index_content'}
<div class="grid_16" id="center">
	<h2>{se name="PalpalPendingTitle"}Vielen Dank f�r Ihre Bestellung.{/se}</h2>
	<div>
		{s name="PalpalPendingInfo"}
		<p>
		Sobald Ihre �berweisung bei PayPal eingegangen ist, werden wir informiert � und verschicken die Ware dann umgehend.<br /><br />
		Sie haben den Betrag noch nicht �berwiesen? Kein Problem. Die Bankverbindung von PayPal k�nnen Sie jederzeit in Ihrem PayPal-Konto abrufen.
		Klicken Sie in der Konto�bersicht direkt neben der Zahlung auf den Link "Details".
		Auf der n�chsten Seite finden Sie unter dem Link "So schlie�en Sie Ihre PayPal-Zahlung per Bank�berweisung ab� alle n�tigen Informationen.
		</p>
		{/s}
	</div>
	<a href="{url controller='index'}" title="{s name='PalpalPendingLinkHomepage'}{/s}" class="button-left large modal_close">
		{se name="PalpalPendingLinkHomepage"}{/se}
	</a>
</div>
{/block}