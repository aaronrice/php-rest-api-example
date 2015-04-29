<?php

class RestApiClientTest extends PHPUnit_Framework_TestCase {
	private $url;
	private $username;
	private $password;
	private $jobId;
	private $filePath;
	
	public function setUp(){
		$this->username = getenv("C2M_USERNAME");
		$this->password = getenv("C2M_PASSWORD");
		$this->url = getenv("C2M_URL");
		$file =  getenv("C2M_DOCUMENT");
		$this->filePath = __DIR__ . '/fixture/docs/' . $file;
	}
	
		
	public function testcreateJobAndSubmit(){
		
		//create document
		$documentId = $this->createDocument();
		$documentId = (array)$documentId;

		//create addressList
		$addressId = $this->createAddressListId();
		$addressId = (array)$addressId;
		
		//create the job
		$job = new job();
		$job->documentClass = "Letter 8.5 x 11";
		$job->layout = "Address on Separate Page";
		$job->productionTime = "Next Day";
		$job->envelope = "#10 Double Window";
		$job->color = "Black and White";
		$job->paperType = "White 24#";
		$job->printOption = "Printing One side";
		$job->documentId = $documentId[0];
		$job->addressId = $addressId[0];

		$job_result = $this->HttpClient("POST", "jobs", $job,"job");
		$result=simplexml_load_string($job_result);
		
		if($result->status == 0){
			$id = $result->id;
			$this->jobId = $id[0];
			print "Job Created, Id : ".$this->jobId."\n";
		}else{
			print "Job Not Created : ".$result->description;
			exit();
		}
		
		//submit the job
		$billing = new billing();
		$billing->billingType="User Credit";
		$submit_result = $this->HttpClient("POST", "jobs/{$this->jobId}/submit",$billing,"job");
		$result=simplexml_load_string($submit_result);
		if($result->status == 0){
			print "Job Submitted\n";
		}else{
			print "Job Not Submitted : ".$result->description;
			exit();
		}
		// get the job status
		$status_result  = $this->HttpClient("GET", "jobs/{$this->jobId}",null,null);
		print "Status : ".simplexml_load_string($status_result)->description;
		
	}

	public function jobCost(){
		$result = $this->HttpClient("GET", "jobs/{$this->jobId}/cost",null,null);
		print $result;
	}

	public function deleteJob(){
		$result  = $this->HttpClient("POST", "jobs/{$this->jobId}/delete",null,"job");
		print $result;
	}
	
	public function createProof(){
		$result = $this->HttpClient("POST", "jobs/{$this->jobId}/proof",null,"job");
		print $result;
	}	
	
	public function purchaseCredit(){
		$billing = new billing();
		$billing->billingAmount ="110.00";
		$billing->billingName = "John Doe";
		$billing->billingCompany = "Abc Company";
		$billing->billingAddress1 = "PO Box 100729";
		$billing->billingCity = "Arlington";
		$billing->billingState = "Va";
		$billing->billingZip = "22210";
		$billing->billingCcType = "VI";
		$billing->billingNumber  = "4111111111111111";
		$billing->billingMonth = "12";
		$billing->billingYear = "16";
		$billing->billingCvv = "123";

		$purchase_result = $this->HttpClient("POST", "credit/purchase",$billing,"purchaseCredit");
		print $purchase_result;
		print "\n";	

		$credit_result = $this->HttpClient("GET", "credit",null,null);
		print $credit_result;
	}
	
	public function uspsTracking(){
		$result = $this->HttpClient("GET", "jobs/{$this->jobId}/tracking?trackingType=IMB",null,null);
		print $result;
	}
	
	
	public function uspsTracking_uniqueID(){
		$result = $this->HttpClient("GET", "jobs/{$this->jobId}/uniqueid/tracking?trackingType=IMB",null,null);
		print $result;
	}
	
	
	public function jobInfo(){
		$result = $this->HttpClient("GET", "jobs/info/{$this->jobId}?mappingHeadings=yes",null,null);
		print $result;
	}
	

	public function createAddressListId(){
		$address = new address();
		$address->first_name = "John";
		$address->last_name = "Doe";
		$address->organization = "Abc Company";
		$address->address1 = "PO Box 100729";
		$address->city = "Arlington";
		$address->state = "VA";
		$address->postalCode = "22210";
		$address->country_non_us = "";
		
		$addresses = new addresses();
		$addresses->addAddress($address);
		$addresses->addAddress($address);
	
		$addresses_result = $this->createAddressList($addresses);
		$id = simplexml_load_string($addresses_result)->id;
		print "AddressList Created, Id : ".$id."\n";
		return $id;
	}
	
	public function createAddressList($addresses){
		$this -> addressListxml = new SimpleXMLElement('<addressList/>');
		$this -> addressListxml -> addChild('addressListName',"testList".substr( md5(rand()), 0, 7));
		$this -> addressListxml -> addChild('addressMappingId', '2');
		$addressesXml = $this -> addressListxml -> addChild('addresses');
		foreach ($addresses->addresses as $address) {
			$addressXml = $addressesXml -> addChild('address');
			$addressXml -> addChild('first_name', $address -> first_name);
			$addressXml -> addChild('last_name', $address -> last_name);
			$addressXml -> addChild('organization', $address -> organization);
			$addressXml -> addChild('address1', $address -> address1);
			$addressXml -> addChild('city', $address -> city);
			$addressXml -> addChild('state', $address -> state);
			$addressXml -> addChild('zip', $address -> postalCode);
			$addressXml -> addChild('country_non-us', $address -> country_non_us);
		}	
		$result = $this->HttpClient("POST", "addressLists",  $this->addressListxml->asXML(),"address");
		return $result;
	}
	
	public function reteriveaddressLists(){
		$result = $this->HttpClient("GET", "addressLists",3,null);
		print $result;
	}	
	
	
	public function addressCorrection(){
		$address = new addressCorrection();
		$address->name = "John";
		$address->address1 = "PO Box 100729";
		$address->city = "Arlington";
		$address->state = "VA";
		$address->zip = "22210";
		
		$addresses = new addresses();
		$addresses->addAddress($address);
		$addresses_result = $this->addressCorrectionXml($addresses);
		print $addresses_result;
	}
	
	
	public function addressCorrectionXml($addresses){
		$this -> addresscorrectionxml = new SimpleXMLElement('<addresses/>');
		foreach ($addresses->addresses as $address) {
			$addressXml = $this -> addresscorrectionxml -> addChild('address');
			$addressXml -> addChild('name', $address -> name);
			$addressXml -> addChild('address1', $address -> address1);
			$addressXml -> addChild('city', $address -> city);
			$addressXml -> addChild('state', $address -> state);
			$addressXml -> addChild('zip', $address -> zip);
		}
		$result = $this->HttpClient("POST", "addressCorrection",  $this->addresscorrectionxml->asXML(),"address");
		return $result;
	}
	

	public function createDocument(){
		$document = new document();
		$document->documentName = "testDoc".substr( md5(rand()), 0, 7);
		$document->documentClass = "Letter 8.5 x 11";
		$document->documentFormat = "DOCX";
		$document->file ='@'.$this->filePath;
		$document_result = $this->HttpClient("POST", "documents", $document,"document");
		print $document_result;
		$Id = simplexml_load_string($document_result)->id;
		print "\nDocument Created, Id : ".$Id."\n";
		print "Document name : ".$document->documentName."\n";
		return $Id;
	}
	
	public function reterivedocumentsLists(){
		$result = $this->HttpClient("GET", "documents",3,null);
		print $result;	
	}	
	
	public function mergeDocuments(){
		$this -> documentXml = new SimpleXMLElement('<documentList/>');
		$this -> documentXml -> addChild('documentId', '');
		$this -> documentXml -> addChild('documentId', '');
		$result = $this->HttpClient("POST", "documents/merge?format=xml", $this -> documentXml->asXML(),"mergeDocuments");
		print $result ;
	}
	
	function HttpClient($method, $path, $data = null,$type) {
		$curl_url = $this->url . $path;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
		curl_setopt($ch, CURLOPT_URL, $curl_url);
		
		if ($method == "POST") {
			curl_setopt($ch, CURLOPT_POST,1);
			if($type == "document"){
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			if($type == "address" || $type == "mergeDocuments"){
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			
			if($type == "job" || $type == "purchaseCredit"){
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(json_decode(json_encode((array)$data))));
			}
		}
		if ($method == "GET") {
			$data = (array)$data;
			if(sizeof($data)>0){
				$postvars = http_build_query(json_decode(json_encode($data),true));
				$curl_url = $curl_url."?".$postvars;
				print $curl_url."\n";
			}
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$response_info =  curl_getinfo($ch);
		if (curl_errno($ch)) {
			print curl_error($ch);
			print "<br>Unable to complete request.";
			exit();
		}
		curl_close($ch);
		try {
			if($response_info['content_type'] == 'application/xml' && $response_info['http_code'] == 500){
	    			return $response;
			}else if($response_info['content_type'] == 'application/xml' || $response_info['http_code']!= 500){
				return simplexml_load_string($response)->asXML();
			}else{
				return $response;
			}
		} catch(Exception $e) {
			return $response;
		}
	}
}

class job {
	public $documentClass;
	public $layout;
	public $productionTime;
	public $envelope;
	public $color;
	public $paperType;
	public $printOption;
	public $documentId;
	public $addressId;
	public $mailingDate;
}

class document {
	public $documentName;
	public $documentClass;
	public $documentFormat;
	public $file;
}

class address {
	public $first_name;
	public $last_name;
	public $organization;
	public $address1;
	public $city;
	public $state;
	public $postalCode;
	public $country_non_us;
}

class addressCorrection {
	public $name;
	public $address1;
	public $city;
	public $state;
	public $zip;
}

class addresses {
	public $addresses = array();

	public function addAddress($address) {
		$this -> addresses[] = $address;
	}
}
class billing {
	public $billingType;
	public $billingAmount;
	public $billingName;
	public $billingAddress1;
	public $billingCity;
	public $billingState;
	public $billingZip;
	public $billingCcType;
	public $billingNumber;
	public $billingMonth;
	public $billingYear;
	public $billingCvv;
}
