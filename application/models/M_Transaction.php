<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of M_Screen1
 *
 * @author endro.ngujiharto
 */
class M_transaction extends CI_Model {
     private $DB1 = NULL;
    
    public function __construct() 
    {
        parent::__construct(); 
    }
    
    function addFaktur($data = ''){
//        $this->DB1 = $this->load->database('mssql_trial', TRUE);
        $this->DB1 = $this->load->database('mssql_common', TRUE);
        
        
        $this->DB1->insert('T_EFaktur', $data);
    }
    
    function checkFaktur($fakturno = ''){
//        $this->DB1 = $this->load->database('mssql_trial', TRUE);
        $this->DB1 = $this->load->database('mssql_common', TRUE);
        
        $q="select NOMOR_FAKTUR "
            . "from T_EFaktur "
            . "where NOMOR_FAKTUR = '$fakturno'";
                
        $query = $this->DB1->query($q);
        return $query->num_rows();
    }
    
    function getFaktur(){
//        $this->DB1 = $this->load->database('mssql_trial', TRUE);
        $this->DB1 = $this->load->database('mssql_common', TRUE);
        
        $q="select FM, KD_JENIS_TRANSAKSI, FG_PENGGANTI, NOMOR_FAKTUR, MASA_PAJAK, TAHUN_PAJAK, TANGGAL_FAKTUR, NPWP, NAMA, ALAMAT_LENGKAP, JUMLAH_DPP, JUMLAH_PPN, JUMLAH_PPNBM, IS_CREDITABLE, STATUS_FAKTUR
            from T_EFaktur
	    where IS_EMAIL = 0";
                
        $query = $this->DB1->query($q);
        return $query->result_array();
    }
    
    function updateFaktur($data = ''){
//        $this->DB1 = $this->load->database('mssql_trial', TRUE);
        $this->DB1 = $this->load->database('mssql_common', TRUE);
        
        $this->DB1->where('IS_EMAIL', 0);
        $this->DB1->update('T_EFaktur', $data);
    }
    
}
