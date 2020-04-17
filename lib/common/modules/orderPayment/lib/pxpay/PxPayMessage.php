<?php
#******************************************************************************
# Abstract base class for PxPay messages.
# These are messages with certain defined elements,  which can be serialized to XML.

#******************************************************************************
class PxPayMessage {
	var $TxnType;
	var $CurrencyInput;
	var $TxnData1;
	var $TxnData2;
	var $TxnData3;
	var $MerchantReference;
	var $EmailAddress;
	var $BillingId;
	var $TxnId;

	function __construct(){

	}

	function setBillingId($BillingId){
		$this->BillingId = $BillingId;
	}
	function getBillingId(){
		return $this->BillingId;
	}
	function setTxnType($TxnType){
		$this->TxnType = $TxnType;
	}
	function getTxnType(){
		return $this->TxnType;
	}
	function setCurrencyInput($CurrencyInput){
		$this->CurrencyInput = $CurrencyInput;
	}
	function getCurrencyInput(){
		return $this->CurrencyInput;
	}
	function setMerchantReference($MerchantReference){
		$this->MerchantReference = $MerchantReference;
	}
	function getMerchantReference(){
		return $this->MerchantReference;
	}
	function setEmailAddress($EmailAddress){
		$this->EmailAddress = $EmailAddress;
	}
	function getEmailAddress(){
		return $this->EmailAddress;
	}
	function setTxnData1($TxnData1){
		$this->TxnData1 = $TxnData1;
	}
	function getTxnData1(){
		return $this->TxnData1;
	}
	function setTxnData2($TxnData2){
		$this->TxnData2 = $TxnData2;
	}
	function getTxnData2(){
		return $this->TxnData2;
	}
	function getTxnData3(){
		return $this->TxnData3;
	}
	function setTxnData3($TxnData3){
		$this->TxnData3 = $TxnData3;
	}
	function setTxnId( $TxnId)
	{
		$this->TxnId = $TxnId;
	}
	function getTxnId(){
		return $this->TxnId;
	}

	function toXml(){
		$arr = get_object_vars($this);

		$xml  = "<GenerateRequest>";

		//		while (list($prop, $val) = each($arr))
//			$xml .= "<$prop>$val</$prop>" ;
//
		foreach($arr as $prop => $val){
			$xml .= "<$prop>$val</$prop>" ;
		}

		$xml .= "</GenerateRequest>";
		return $xml;
	}


}
