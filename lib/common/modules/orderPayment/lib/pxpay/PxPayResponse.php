<?php

#******************************************************************************
# Class for PxPay response messages.
#******************************************************************************

class PxPayResponse extends PxPayMessage
{
	var $Success;
	var $AuthCode;
	var $CardName;
	var $CardHolderName;
	var $CardNumber;
	var $DateExpiry;
	var $ClientInfo;
	var $DpsTxnRef;
	var $DpsBillingId;
	var $AmountSettlement;
	var $CurrencySettlement;
	var $TxnMac;
	var $ResponseText;


	function __construct($xml){
		$msg = new MifMessage($xml);
		parent::__construct();

		$this->Success = $msg->get_element_text("Success");
		$this->setTxnType($msg->get_element_text("TxnType"));
		$this->CurrencyInput = $msg->get_element_text("CurrencyInput");
		$this->setMerchantReference($msg->get_element_text("MerchantReference"));
		$this->setTxnData1($msg->get_element_text("TxnData1"));
		$this->setTxnData2($msg->get_element_text("TxnData2"));
		$this->setTxnData3($msg->get_element_text("TxnData3"));
		$this->AuthCode = $msg->get_element_text("AuthCode");
		$this->CardName = $msg->get_element_text("CardName");
		$this->CardHolderName = $msg->get_element_text("CardHolderName");
		$this->CardNumber = $msg->get_element_text("CardNumber");
		$this->DateExpiry = $msg->get_element_text("DateExpiry");
		$this->ClientInfo = $msg->get_element_text("ClientInfo");
		$this->TxnId = $msg->get_element_text("TxnId");
		$this->setEmailAddress($msg->get_element_text("EmailAddress"));
		$this->DpsTxnRef = $msg->get_element_text("DpsTxnRef");
		$this->BillingId = $msg->get_element_text("BillingId");
		$this->DpsBillingId = $msg->get_element_text("DpsBillingId");
		$this->AmountSettlement = $msg->get_element_text("AmountSettlement");
		$this->CurrencySettlement = $msg->get_element_text("CurrencySettlement");
		$this->TxnMac = $msg->get_element_text("TxnMac");
		$this->ResponseText = $msg->get_element_text("ResponseText");

	}


	function getSuccess(){
		return $this->Success;
	}
	function getAuthCode(){
		return $this->AuthCode;
	}
	function getCardName(){
		return $this->CardName;
	}
	function getCardHolderName(){
		return $this->CardHolderName;
	}
	function getCardNumber(){
		return $this->CardNumber;
	}
	function getDateExpiry(){
		return $this->DateExpiry;
	}
	function getClientInfo(){
		return $this->ClientInfo;
	}
	function getDpsTxnRef(){
		return $this->DpsTxnRef;
	}
	function getDpsBillingId(){
		return $this->DpsBillingId;
	}
	function getAmountSettlement(){
		return $this->AmountSettlement;
	}
	function getCurrencySettlement(){
		$this->CurrencySettlement;
	}
	function getTxnMac(){
		return $this->TxnMac;
	}
	function getResponseText(){
		return $this->ResponseText;
	}
}