<?php
/**
* Generate customer detail document using WP DocGen
* 
* File: class-customer-detail-module.php
* Path: /wp-customer/includes/docgen/customer-detail/class-customer-detail-module.php
*/ 
class WP_Customer_Customer_Detail_Module {
	/*
	*	
	* Generate customer detail document using WP DocGen
	* Generates DOCX file from customer data and template.
	* Will check nonce and user permissions before generating.
	* Document will be saved to WordPress uploads directory.
	* Returns file URL and filename in JSON response.
	*
	* @since 1.0.0
	* @access public
	*
	* @uses \WP_DocGen For document generation
	* @uses Customer_Detail_Provider To provide customer data
	* 
	* @throws \Exception If invalid ID, no permissions, or generation fails
	* @return void Sends JSON response
	*
	*/
	public function generate_wp_docgen_customer_detail_document() {
	    try {
	        check_ajax_referer('wp_customer_nonce', 'nonce');
	        
	        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	        if (!$id) {
	            throw new \Exception('Invalid customer ID');
	        }

	        // Validate access
	        $access = $this->validator->validateAccess($id);
	        if (!$access['has_access']) {
	            throw new \Exception('You do not have permission to view this customer');
	        }

	        // Get customer data
	        $customer = $this->getCustomerData($id);
	        if (!$customer) {
	            throw new \Exception('Customer not found');
	        }

	        // Create provider
	        $provider = new Customer_Document_Provider($customer);

	        // Generate document
	        $docgen = wp_docgen();
	        $output_path = $docgen->generate($provider);

	        if (is_wp_error($output_path)) {
	            throw new \Exception($output_path->get_error_message());
	        }

	        // Prepare download response
	        $file_url = wp_upload_dir()['url'] . '/' . basename($output_path);
	        wp_send_json_success([
	            'file_url' => $file_url,
	            'filename' => basename($output_path)
	        ]);

	    } catch (\Exception $e) {
	        wp_send_json_error(['message' => $e->getMessage()]);
	    }
	}

}

