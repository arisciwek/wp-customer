<?php
/**
* Generate customer detail document using WP DocGen
* 
* File: class-customer-detail-document-provider.php
* Path: /wp-customer/includes/docgen/customer-detail/class-customer-detail-provider.php
*/ 

class WP_Customer_Customer_Detail_Provider implements WP_DocGen_Provider {
    private $customer;
    private $template_path;
    private $output_dir;
    
    public function __construct($customer) {
        $this->customer = $customer;
        $this->template_path = WP_CUSTOMER_PATH . 'templates/docx/customer-detail.docx';
        $this->output_dir = wp_upload_dir()['path'];
    }
    
    public function get_data() {
        return [
            'customer_name' => $this->customer->name,
            'customer_code' => $this->customer->code,
            'total_branches' => $this->customer->branch_count,
            'created_date' => date('d F Y H:i', strtotime($this->customer->created_at)),
            'updated_date' => date('d F Y H:i', strtotime($this->customer->updated_at)),
            'npwp' => $this->customer->npwp ?? '-',
            'nib' => $this->customer->nib ?? '-',
            'generated_date' => date('d F Y H:i')
        ];
    }
    
    public function get_template_path() {
        return $this->template_path;
    }
    
    public function get_output_filename() {
        return 'customer-' . $this->customer->code;
    }
    
    public function get_output_format() {
        return 'docx';
    }
    
    public function get_temp_dir() {
        return $this->output_dir;
    }
}
