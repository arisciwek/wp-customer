<?php
namespace CustomerManagement\Models;

class CustomerModel extends BaseModel {
    protected function get_table_name() {
        return 'customers';
    }

    public function get_with_relations($id) {
        $sql = $this->wpdb->prepare("
            SELECT 
                c.*, 
                e.name as employee_name,
                e.position as employee_position,
                b.name as branch_name,
                b.location as branch_location,
                u.display_name as created_by_name,
                ua.display_name as assigned_to_name
            FROM {$this->table_name} c
            LEFT JOIN {$this->wpdb->prefix}customer_employees e ON c.employee_id = e.id
            LEFT JOIN {$this->wpdb->prefix}customer_branches b ON c.branch_id = b.id
            LEFT JOIN {$this->wpdb->users} u ON c.created_by = u.ID
            LEFT JOIN {$this->wpdb->users} ua ON c.assigned_to = ua.ID
            WHERE c.id = %d
        ", $id);

        return $this->wpdb->get_row($sql);
    }

    public function get_by_membership($type) {
        return $this->get_all(['where' => ['membership_type' => $type]]);
    }

    public function get_assigned_to_user($user_id) {
        return $this->get_all([
            'where' => [
                'assigned_to' => $user_id
            ]
        ]);
    }

    public function get_created_by_user($user_id) {
        return $this->get_all([
            'where' => [
                'created_by' => $user_id
            ]
        ]);
    }

    public function get_by_location($provinsi_id, $kabupaten_id = null) {
        $where = ['provinsi_id' => $provinsi_id];
        if ($kabupaten_id) {
            $where['kabupaten_id'] = $kabupaten_id;
        }
        return $this->get_all(['where' => $where]);
    }

    // In Customer.php, update the get_for_datatable method:

    public function get_for_datatable($args = []) {
        $defaults = [
            'start' => 0,
            'length' => 10,
            'search' => '',
            'order_column' => 0,
            'order_dir' => 'ASC'
        ];

        $args = wp_parse_args($args, $defaults);

        // Map DataTables column index to actual column names
        $columns = [
            0 => 'c.name',
            1 => 'c.email',
            2 => 'c.phone',
            3 => 'c.membership_type',
            4 => 'b.name',
            5 => 'e.name'
        ];

        // Get the actual column name from the index
        $order_column = isset($columns[$args['order_column']]) ? 
                       $columns[$args['order_column']] : 
                       'c.name';

        $sql = $this->wpdb->prepare("
            SELECT 
                c.*,
                COALESCE(e.name, '') as employee_name,
                COALESCE(b.name, '') as branch_name,
                ml.name as membership_level_name
            FROM {$this->table_name} c
            LEFT JOIN {$this->wpdb->prefix}customer_employees e ON c.employee_id = e.id
            LEFT JOIN {$this->wpdb->prefix}customer_branches b ON c.branch_id = b.id
            LEFT JOIN {$this->wpdb->prefix}customer_membership_levels ml ON c.membership_level_id = ml.id
            WHERE c.status = 'active'
            ORDER BY {$order_column} {$args['order_dir']}
            LIMIT %d, %d
        ", $args['start'], $args['length']);

        return $this->wpdb->get_results($sql);
    }
}