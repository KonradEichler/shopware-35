{if $sUserData.additional.country.countryiso == "DE" or $sUserData.additional.country.countryiso == "AT"}
{if $_GET.sTarget}
	<input name="sTarget" type="hidden" value="sale" />
{/if}
<div class="paypoint">
	<input class="radio" name="sPayment" id="heidelpay_dd" value="{$sPayment.id}" type="radio" {if $sChoosenPayment==$sPayment.id OR (!$sChoosenPayment AND $sConfig.sDEFAULTPAYMENT==$sPayment.id)}checked{/if} />
	<label class="paylabel" for="heidelpay_dd">{$sPayment.description}</label> {if $sChoosenPayment==$sPayment.id}<span class="enabled">{* sSnippet: currently selected *}{$sConfig.sSnippets.sPaymentcurrentlyselected}</span>{/if}<br />

    {if $sPayment.additionaldescription}<p class="paydescr">{$sPayment.additionaldescription}</p>{/if}
</div>
{/if}
