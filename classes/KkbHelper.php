<?php

class KKBsign {
    // -----------------------------------------------------------------------------------------------
    function load_private_key($filename, $password = NULL){
        $this->ecode=0;
        if(!is_file($filename)){ $this->ecode=4; $this->estatus = "[KEY_FILE_NOT_FOUND]"; return false;};
        $c = file_get_contents($filename);
        if(strlen(trim($password))>0){$prvkey = openssl_get_privatekey($c, $password); $this->parse_errors(openssl_error_string());
        } else {$prvkey = openssl_get_privatekey($c); $this->parse_errors(openssl_error_string());};
        if(is_resource($prvkey)){ $this->private_key = $prvkey; return $c;}
        return false;
    }
    // -----------------------------------------------------------------------------------------------
    // ��������� ����� ��������
    function invert(){ $this->invert = 1;}
    // -----------------------------------------------------------------------------------------------
    // ������� �������� ������
    function reverse($str){	return strrev($str);}
    // -----------------------------------------------------------------------------------------------
    function sign($str){
        if($this->private_key){
            openssl_sign($str, $out, $this->private_key);
            if($this->invert == 1) $out = $this->reverse($out);
            //openssl_free_key($this->private_key);
            return $out;
        };
    }
    // -----------------------------------------------------------------------------------------------
    function sign64($str){	return base64_encode($this->sign($str));}
    // -----------------------------------------------------------------------------------------------
    function check_sign($data, $str, $filename){
        if($this->invert == 1)  $str = $this->reverse($str);
        if(!is_file($filename)){ $this->ecode=4; $this->estatus = "[KEY_FILE_NOT_FOUND]"; return 2;};
        $this->pubkey = file_get_contents($filename);
        $pubkeyid = openssl_get_publickey($this->pubkey);
        $this->parse_errors(openssl_error_string());
        if (is_resource($pubkeyid)){
            $result = openssl_verify($data, $str, $pubkeyid);
            $this->parse_errors(openssl_error_string());
            openssl_free_key($pubkeyid);
            return $result;
        };
        return 3;
    }
    // -----------------------------------------------------------------------------------------------
    function check_sign64($data, $str, $filename){
        return $this->check_sign($data, base64_decode($str), $filename);
    }
    // -----------------------------------------------------------------------------------------------
    function parse_errors($error){
        // -----===++[Parses error to errorcode and message]++===-----
        /*error:0906D06C - Error reading Certificate. Verify Cert type.
        error:06065064 - Bad decrypt. Verify your Cert password or Cert type.
        error:0906A068 - Bad password read. Maybe empty password.*/
        if (strlen($error)>0){
            if (strpos($error,"error:0906D06C")>0){$this->ecode = 1; $this->estatus = "Error reading Certificate. Verify Cert type.";};
            if (strpos($error,"error:06065064")>0){$this->ecode = 2; $this->estatus = "Bad decrypt. Verify your Cert password or Cert type.";};
            if (strpos($error,"error:0906A068")>0){$this->ecode = 3; $this->estatus = "Bad password read. Maybe empty password.";};
            if ($this->ecode = 0){$this->ecode = 255; $this->estatus = $error;};
        };
    }
};

class xml {

    var $parser;
    var $xarray = array();
    var $lasttag;

    function xml()
    {   $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($this->parser, "tag_open", "tag_close");
        xml_set_character_data_handler($this->parser, "cdata");
    }

    function parse($data)
    {
        xml_parse($this->parser, $data);
        ksort($this->xarray,SORT_STRING);
        return $this->xarray;
    }

    function tag_open($parser, $tag, $attributes)
    {
        $this->lasttag = $tag;
        $this->xarray['TAG_'.$tag] = $tag;
        if (is_array($attributes)){
            foreach ($attributes as $key => $value) {
                $this->xarray[$tag.'_'.$key] = $value;
            };
        };
    }

    function cdata($parser, $cdata)
    {	$tag = $this->lasttag;
        $this->xarray[$tag.'_CHARDATA'] = $cdata;
    }

    function tag_close($parser, $tag)
    {}
}
// -----------------------------------------------------------------------------------------------
function process_XML($filename,$reparray) {
    // -----===++[Process XML template - replaces tags in file to array values]++===-----
    // variables:
    // $filename - string: name of XML template
    // $reparray - array: data to replace
    //
    // XML template tag format:[tag name] example: [MERCHANT_CERTIFICATE_ID]
    //
    // Functionality:Searches file for array index and replaces to value
    // example: in array > $reparray['MERCHANT_CERTIFICATE_ID'] = "12345"
    // before replace: cert_id="[MERCHANT_CERTIFICATE_ID]"
    // after replace: cert_id="12345"
    // if operation successful returns file contents with replaced values
    // if template not found returns "[ERROR]"
    //
    // -----===++[��������� XML ������� - ������ ����� � ����� �� �������� �� �������]++===-----
    // ����������:
    // $filename - ������: ��� XML �������
    // $reparray - ������: ������ ��� ������
    //
    // ������ ����� � XML �������:[tag name] ������: [MERCHANT_CERTIFICATE_ID]
    //
    // ����������������: ���� � ������� ������� ������� � �������� �� �� ��������
    // ������: � ������� > $reparray['MERCHANT_CERTIFICATE_ID'] = "12345"
    // ����� �������: cert_id="[MERCHANT_CERTIFICATE_ID]"
    // ����� ������: cert_id="12345"
    // ���� �������� ������ ������� ���������� ����� ����� � ����������� ����������
    // ���� ���� ������� �� ����� ���������� "[ERROR]"

    if(is_file($filename)){
        $content = file_get_contents($filename);
        foreach ($reparray as $key => $value) {$content = str_replace("[".$key."]",$value,$content);};
        return $content;
    } else {return "[ERROR]";};
};

function createQuery($template, $repArray) {
    $content = $template;
    foreach ($repArray as $key => $value)
    {
        $content = str_replace("[".$key."]",$value, $content);
    };

    return $content;

};
// -----------------------------------------------------------------------------------------------
function split_sign($xml,$tag){
    // -----===++[Process XML string to array of values]++===-----
    // variables:
    // $xml - string: xml string
    // $tag - string: split tag name
    // $array["LETTER"] = an XML section enclosed in <$tag></$tag>
    // $array["SIGN"] = an XML sign section enclosed in <$tag+"_sign"></$tag+"_sign">
    // $array["RAWSIGN"] = an XML sign section with stripped <$tag+"_sign"></$tag+"_sign"> tags
    // example:
    // income data:
    // $xml = "<order order_id="12345"><department amount="10"/></order><order_sign type="SHA/RSA">ljkhsdfmnuuewrhkj</order_sign>"
    // $tag = "ORDER"
    // result:
    // $array["LETTER"] = "<order order_id="12345"><department amount="10"/></order>"
    // $array["SIGN"] = "<order_sign type="SHA/RSA">ljkhsdfmnuuewrhkj</order_sign>"
    // $array["RAWSIGN"] = "ljkhsdfmnuuewrhkj"
    //
    // -----===++[��������� XML ������ � ��������������]++===-----
    // ����������:
    // $xml - ������: xml ������
    // $tag - ������: ��� ���� �����������
    // $array["LETTER"] = XML ������ ����������� � <$tag></$tag>
    // $array["SIGN"] = XML ������ ������� ����������� � <$tag+"_sign"></$tag+"_sign">
    // $array["RAWSIGN"] = XML ������ ������� � ����������� <$tag+"_sign"></$tag+"_sign"> ������
    // ������:
    // ������� ������:
    // $xml = "<order order_id="12345"><department amount="10"/></order><order_sign type="SHA/RSA">ljkhsdfmnuuewrhkj</order_sign>"
    // $tag = "ORDER"
    // ���������:
    // $array["LETTER"] = "<order order_id="12345"><department amount="10"/></order>"
    // $array["SIGN"] = "<order_sign type="SHA/RSA">ljkhsdfmnuuewrhkj</order_sign>"
    // $array["RAWSIGN"] = "ljkhsdfmnuuewrhkj"


    $array = array();
    $letterst = stristr($xml,"<".$tag);
    $signst = stristr($xml,"<".$tag."_SIGN");
    $signed = stristr($xml,"</".$tag."_SIGN");
    $doced = stristr($signed,">");
    $array['LETTER'] = substr($letterst,0,-strlen($signst));
    $array['SIGN'] = substr($signst,0,-strlen($doced)+1);
    $rawsignst = stristr($array['SIGN'],">");
    $rawsigned = stristr($rawsignst,"</");
    $array['RAWSIGN'] = substr($rawsignst,1,-strlen($rawsigned));
    return $array;
}

class KkbHelper
{
    private $MERCHANT_CERTIFICATE_ID;
    private $MERCHANT_NAME;
    private $MERCHANT_ID;
    private $PRIVATE_KEY_PATH;
    private $PRIVATE_KEY_PASS;
    private $XML_TEMPLATE;
    private $XML_COMMAND_TEMPLATE;
    private $PUBLIC_KEY_PATH;
    private $ACTION_URL;

    public function __construct($MERCHANT_CERTIFICATE_ID, $MERCHANT_NAME, $MERCHANT_ID, $PRIVATE_KEY_PATH, $PRIVATE_KEY_PASS,  $PUBLIC_KEY_PATH, $ACTION_URL)
    {
        $this->MERCHANT_CERTIFICATE_ID = $MERCHANT_CERTIFICATE_ID;
        $this->MERCHANT_NAME = $MERCHANT_NAME;
        $this->MERCHANT_ID = $MERCHANT_ID;
        $this->PRIVATE_KEY_PATH = $PRIVATE_KEY_PATH;
        $this->PRIVATE_KEY_PASS = $PRIVATE_KEY_PASS;

        $this->PUBLIC_KEY_PATH = $PUBLIC_KEY_PATH;

        $this->XML_TEMPLATE = '<merchant cert_id="[MERCHANT_CERTIFICATE_ID]" name="[MERCHANT_NAME]"><order order_id="[ORDER_ID]" amount="[AMOUNT]" currency="[CURRENCY]"><department merchant_id="[MERCHANT_ID]" amount="[AMOUNT]"/></order></merchant>';
        $this->XML_COMMAND_TEMPLATE = '<merchant id="[MERCHANT_ID]"><command type="[COMMAND]"/><payment reference="[REFERENCE_ID]" approval_code="[APPROVAL_CODE]" orderid="[ORDER_ID]" amount="[AMOUNT]" currency_code="[CURRENCY]"/><reason>[REASON]</reason></merchant>';
        $this->ACTION_URL = $ACTION_URL;
    }

    public function getActionUrl()
    {
        return $this->ACTION_URL;
    }

    public function process_request($order_id, $currency_code, $amount, $b64=true) {

        if (strlen($order_id)>0){
            if (is_numeric($order_id)){
                if ($order_id>0){
                    $order_id = sprintf ("%06d",$order_id);
                } else { return "Null Order ID";};
            } else { return "Order ID must be number";};
        } else { return "Empty Order ID";};

        if (strlen($currency_code)==0){return "Empty Currency code";};
        if ($amount==0){return "Nothing to charge";};

        $request = array();
        $request['MERCHANT_CERTIFICATE_ID'] = $this->MERCHANT_CERTIFICATE_ID;
        $request['MERCHANT_NAME'] = $this->MERCHANT_NAME;
        $request['ORDER_ID'] = $order_id;
        $request['CURRENCY'] = $currency_code;
        $request['MERCHANT_ID'] = $this->MERCHANT_ID;
        $request['AMOUNT'] = $amount;

        $kkb = new KKBSign();
        $kkb->invert();
        if (!$kkb->load_private_key($this->PRIVATE_KEY_PATH, $this->PRIVATE_KEY_PASS)){
            if ($kkb->ecode>0){return $kkb->estatus;};
        };

        $result = createQuery($this->XML_TEMPLATE, $request);
        if (strpos($result,"[RERROR]")>0){ return "Error reading XML template.";};

        $result_sign = '<merchant_sign type="RSA">'.$kkb->sign64($result).'</merchant_sign>';
        $xml = "<document>".$result.$result_sign."</document>";
        if ($b64){return base64_encode($xml);} else {return $xml;};
    }

    public function process_response($response) {

        $xml_parser = new xml();
        $result = $xml_parser->parse($response);
        if (in_array("ERROR",$result)){
            return $result;
        };
        if (in_array("DOCUMENT",$result)){
            $kkb = new KKBSign();
            $kkb->invert();
            $data = split_sign($response, "BANK");
            $check = $kkb->check_sign64($data['LETTER'], $data['RAWSIGN'], $this->PUBLIC_KEY_PATH);
            if ($check == 1)
                $data['CHECKRESULT'] = "[SIGN_GOOD]";
            elseif ($check == 0)
                $data['CHECKRESULT'] = "[SIGN_BAD]";
            else
                $data['CHECKRESULT'] = "[SIGN_CHECK_ERROR]: ".$kkb->estatus;
            return array_merge($result,$data);
        };
        return "[XML_DOCUMENT_UNKNOWN_TYPE]";
    }

    public function process_refund($reference, $approval_code, $order_id, $currency_code, $amount, $reason) {

        if(!$reference) return "Empty Transaction ID";


        if (strlen($order_id)>0){
            if (is_numeric($order_id)){
                if ($order_id>0){
                    $order_id = sprintf ("%06d",$order_id);
                } else { return "Null Order ID";};
            } else { return "Order ID must be number";};
        } else { return "Empty Order ID";};

        if(!$reason) $reason = "Transaction revert";

        if (strlen($currency_code)==0){return "Empty Currency code";};
        if ($amount==0){return "Nothing to charge";};

        $request = array();
        $request['MERCHANT_ID'] = $this->MERCHANT_ID;
        $request['MERCHANT_NAME'] = $this->MERCHANT_NAME;
        $request['COMMAND'] = 'reverse';
        $request['REFERENCE_ID'] = $reference;
        $request['APPROVAL_CODE'] = $approval_code;
        $request['ORDER_ID'] = $order_id;
        $request['CURRENCY'] = $currency_code;
        //$request['MERCHANT_ID'] = $this->MERCHANT_ID;
        $request['AMOUNT'] = $amount;
        $request['REASON'] = $reason;

        $kkb = new KKBSign();
        $kkb->invert();
        if (!$kkb->load_private_key($this->PRIVATE_KEY_PATH,$this->PRIVATE_KEY_PASS)){
            if ($kkb->ecode>0){return $kkb->estatus;};
        };

        $result = createQuery($this->XML_COMMAND_TEMPLATE, $request);
        if (strpos($result,"[RERROR]")>0){ return "Error reading XML template.";};
        $result_sign = '<merchant_sign type="RSA" cert_id="' . $this->MERCHANT_CERTIFICATE_ID . '">'.$kkb->sign64($result).'</merchant_sign>';
        $xml = "<document>".$result.$result_sign."</document>";
        return $xml;
    }



    public function process_complete($reference, $approval_code, $order_id, $currency_code, $amount) {

        if(!$reference) return "Empty Transaction ID";

        if (strlen($order_id)>0) {
            if (is_numeric($order_id)){
                if ($order_id>0){
                    $order_id = sprintf ("%06d",$order_id);
                } else { return "Null Order ID";};
            } else { return "Order ID must be number";};
        } else { return "Empty Order ID";};

        if (strlen($currency_code)==0){return "Empty Currency code";};
        if ($amount==0){return "Nothing to charge";};

        $request = array();
        $request['MERCHANT_ID'] = $this->MERCHANT_ID;
        $request['MERCHANT_NAME'] = $this->MERCHANT_NAME;
        $request['COMMAND'] = 'complete';
        $request['REFERENCE_ID'] = $reference;
        $request['APPROVAL_CODE'] = $approval_code;
        $request['ORDER_ID'] = $order_id;
        $request['CURRENCY'] = $currency_code;
        //$request['MERCHANT_ID'] = $this->MERCHANT_ID;
        $request['AMOUNT'] = $amount;
        $request['REASON'] = '';

        $kkb = new KKBSign();
        $kkb->invert();
        if (!$kkb->load_private_key($this->PRIVATE_KEY_PATH, $this->PRIVATE_KEY_PASS)){
            if ($kkb->ecode>0){return $kkb->estatus;};
        };

        $result = createQuery($this->XML_COMMAND_TEMPLATE, $request);
        if (strpos($result,"[RERROR]")>0){ return "Error reading XML template.";};
        $result_sign = '<merchant_sign type="RSA" cert_id="' . $this->MERCHANT_CERTIFICATE_ID . '">'.$kkb->sign64($result).'</merchant_sign>';
        $xml = "<document>".$result.$result_sign."</document>";
        return $xml;
    }


    public function request($url)
    {

        //$fOut = fopen('/home/kiwi/external/testcurl.txt', "w" );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // times out after 4s
        //curl_setopt($ch, CURLOPT_STDERR, $fOut );
        //curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        $result = curl_exec($ch); // run the whole process
        curl_close($ch);

//        $xml_parser = new xml();
//        $result = $xml_parser->parse($result);

        return $result;
    }

}