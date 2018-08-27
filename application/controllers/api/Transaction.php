<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Screen 2
 * 
 * Error Code :
 * - 0 = OK
 * - 1 = NG
 *
 * @author endro.ngujiharto
 */

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

class Transaction extends REST_Controller {
    
    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['users_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
        
        $this->load->model('M_Transaction');
        date_default_timezone_set('Asia/Jakarta');
    }
    
    public function index_get(){
        
    }

        public function Submit_post()
    {
        $url = $this->post('barcode');
        $iscreditable = $this->post('credit');
        $xml_str = file_get_contents($url);
        
        $xml = simplexml_load_string($xml_str, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
//        var_dump($array);die();
        $time = strtotime(str_replace('/', '-', $array['tanggalFaktur']));

        $fakturdate = date('Y-m-d',$time);
//        var_dump($array);die();
        
        $response['data'] = null;
        
        if(empty($url) || ($iscreditable != 0 && $iscreditable != 1)){
            $response['error_msg']      = 'Barcode dan Credit Tidak Diterima';
            $response['error_code']     = 1;
        } else {
            $check = $this->M_Transaction->checkFaktur($array['nomorFaktur']);
            if($check > 0){
                $response['error_msg']      = 'Data Sudah di Scan';
                $response['error_code']     = 1;
            } else {
                $data['faktur'] = $array['nomorFaktur'];
                $data['npwp'] = $array['npwpPenjual'];
                $data['company'] = $array['namaPenjual'];
                $data['status_faktur'] = $array['statusFaktur'];
                if(isset($array['detailTransaksi']['ppn'])){
                    $data['ppn'] = $array['detailTransaksi']['ppn'];
                    $dpp = $array['detailTransaksi']['dpp'];
                    $ppnbm = $array['detailTransaksi']['ppnbm'];
                } else {
                    $data['ppn'] = 0;
                    $dpp = 0;
                    $ppnbm = 0;
                    foreach ($array['detailTransaksi'] as $dt){
                        $data['ppn'] += $dt['ppn'];
                        $dpp += $dt['dpp'];
                        $ppnbm += $dt['ppnbm'];
                    }
                    $data['ppn'] = intval($data['ppn']);
                    $dpp = intval($dpp);
                    $ppnbm = intval($ppnbm);
                }
                $response['data']               = $data;
                if($response['data'] != NULL){
                    $response['error_msg']      = 'Data Berhasil di Simpan';
                    $response['error_code']     = 0;
                    $data = array (
                        'FM'                    => 'FM',													
                        'KD_JENIS_TRANSAKSI'    => ltrim($array['kdJenisTransaksi'], '0'),
                        'FG_PENGGANTI'          => $array['fgPengganti'],
                        'TANGGAL_FAKTUR'        => $fakturdate,
                        'NPWP'                  => $array['npwpPenjual'],
                        'NAMA'                  => $array['namaPenjual'],
                        'ALAMAT_LENGKAP'        => $array['alamatPenjual'],
                        'JUMLAH_DPP'            => $dpp,
                        'JUMLAH_PPN'            => $data['ppn'],
                        'JUMLAH_PPNBM'          => $ppnbm,
                        'IS_CREDITABLE'         => $iscreditable,
                        'IS_Email'              => 0,
                        'NOMOR_FAKTUR'          => $array['nomorFaktur'],
                        'STATUS_FAKTUR'         => $array['statusFaktur']
                    );
    //                var_dump($data);die();
                    $this->M_Transaction->addFaktur($data);
                } else {
                    $response['error_msg']      = 'Data Tidak Ditemukan';
                    $response['error_code']     = 1;
                }
            }
        }
        
        $this->response($response, REST_Controller::HTTP_OK);
    }
    
    public function Email_post() {
        $month = $this->post('month');
        $year = $this->post('year');
        
        if(empty($month) || empty($year)){
            $response['error_msg']      = 'Periode Tidak Diterima';
            $response['error_code']     = 1;
        } else {
//            $email="revina.ramadhantie@jst.co.id";
            $email="endro.ngujiharto@jst.co.id";
            $subject="Faktur Pajak Periode ".$month." - ".$year;
            $message="Terlampir";
            $this->exportCSV($month, $year);
            $response = $this->sendEmail($email,$subject,$message,$month,$year);
        }
        
        $this->response($response, REST_Controller::HTTP_OK);
    }
    
    public function sendEmail($email,$subject,$message,$month,$year) {

        $config = Array(
          'protocol'    => 'smtp',
          'smtp_host'   => '192.9.18.2',
          'smtp_port'   => 25,
          'smtp_user'   => 'endro.ngujiharto@jst.co.id', 
          'smtp_pass'   => ''
            . ''
            . ''
            . ''
            . '', 
          'mailtype'    => 'html',
          'charset'     => 'iso-8859-1',
          'wordwrap'    => TRUE
        );
        
        $filename = 'E-Faktur_'.$month.'_'.$year.'.csv';


        $this->load->library('email', $config);
        $this->email->set_newline("\r\n");
        $this->email->from('e-fakturJST@jst.co.id');
        $this->email->to($email);
        $this->email->subject($subject);
        $this->email->message($message);
        $this->email->attach(APPPATH . '/../export/'.$filename);
        $response['data'] =  null;
        if($this->email->send()) {
            $response['error_msg']      = 'Email Berhasil Dikirim';
            $response['error_code']     = 0;
            $update = array(
                'IS_EMAIL'    => 1
            );
            $this->M_Transaction->updateFaktur($update);
            
        } else {
            $response['error_msg']      = $this->email->print_debugger();
            $response['error_code']     = 1;
        }
        
        unlink(APPPATH . '/../export/'.$filename);
        
        return $response;
    }
    
    // Export data in CSV format 
    public function exportCSV($month = '', $year = '') { 
        $update = array(
            'MASA_PAJAK'    => $month,
            'TAHUN_PAJAK'   => $year
        );
        $this->M_Transaction->updateFaktur($update);
        // file name 
        $filename = 'E-Faktur_'.$month.'_'.$year.'.csv'; 
//        header("Content-Description: File Transfer"); 
//        header("Content-Disposition: attachment; filename=$filename"); 
//        header("Content-Type: application/csv; ");

        // get data 
        $usersData = $this->M_Transaction->getFaktur();
//        var_dump($usersData);die();

        // file creation 
//        var_dump(APPPATH);die();
        $file = fopen(APPPATH . '/../export/'.$filename, 'wb');

        $header = array('FM', 'KD_JENIS_TRANSAKSI', 'FG_PENGGANTI', 'NOMOR_FAKTUR', 'MASA_PAJAK', 'TAHUN_PAJAK', 'TANGGAL_FAKTUR', 'NPWP', 'NAMA', 'ALAMAT_LENGKAP', 'JUMLAH_DPP', 'JUMLAH_PPN', 'JUMLAH_PPNBM', 'IS_CREDITABLE', 'STATUS_FAKTUR'); 
        $this->my_fputcsv($file, $header);
        foreach ($usersData as $key=>$line){
            $time = strtotime(str_replace('/', '-', $line['TANGGAL_FAKTUR']));
            $fakturdate = date('d/m/Y',$time);
            $line['TANGGAL_FAKTUR'] = $fakturdate;
            $this->my_fputcsv($file,$line);
        }
    }
    
    function my_fputcsv($handle, $fieldsarray, $delimiter = ",", $enclosure ='"'){
        $glue = $enclosure . $delimiter . $enclosure;
        return fwrite($handle, $enclosure . implode($glue,$fieldsarray) . $enclosure."\r\n");
    }
}
