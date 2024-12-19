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
                ml.name as membership_level_name,
                ml.slug as membership_level_slug,
                COALESCE(wp.name, '-') as province_name,
                COALESCE(wr.name, '-') as city_name,
                ua.display_name as assigned_to_name
            FROM {$this->table_name} c
            LEFT JOIN {$this->wpdb->prefix}customer_employees e ON c.employee_id = e.id
            LEFT JOIN {$this->wpdb->prefix}customer_branches b ON c.branch_id = b.id
            LEFT JOIN {$this->wpdb->prefix}customer_membership_levels ml ON c.membership_level_id = ml.id
            LEFT JOIN {$this->wpdb->base_prefix}wi_provinces wp ON c.provinsi_id = wp.id
            LEFT JOIN {$this->wpdb->base_prefix}wi_regencies wr ON c.kabupaten_id = wr.id
            LEFT JOIN {$this->wpdb->users} u ON c.created_by = u.ID
            LEFT JOIN {$this->wpdb->users} ua ON c.assigned_to = ua.ID
            WHERE c.id = %d
        ", $id);

        return $this->wpdb->get_row($sql);
    }

    public function _get_with_relations($id) {
        $sql = $this->wpdb->prepare("
            SELECT 
                c.*, 
                e.name as employee_name,
                e.position as employee_position,
                b.name as branch_name,
                b.location as branch_location,
                ml.name as membership_level_name,
                ml.slug as membership_level_slug,
                ua.display_name as assigned_to_name
            FROM {$this->table_name} c
            LEFT JOIN {$this->wpdb->prefix}customer_employees e ON c.employee_id = e.id
            LEFT JOIN {$this->wpdb->prefix}customer_branches b ON c.branch_id = b.id
            LEFT JOIN {$this->wpdb->prefix}customer_membership_levels ml ON c.membership_level_id = ml.id
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

    public function get_for_datatable($args = []) {
        $defaults = [
            'start' => 0,
            'length' => 10,
            'search' => '',
            'order_column' => 0,
            'order_dir' => 'ASC'
        ];

        $args = wp_parse_args($args, $defaults);

        $columns = [
            0 => 'c.name',
            1 => 'c.email',
            2 => 'c.phone',
            3 => 'ml.name',
            4 => 'b.name'
        ];

        $order_column = isset($columns[$args['order_column']]) ? 
                       $columns[$args['order_column']] : 
                       'c.name';

        $sql = $this->wpdb->prepare("
            SELECT 
                c.*,
                COALESCE(e.name, '') as employee_name,
                COALESCE(b.name, '') as branch_name,
                COALESCE(ml.name, 'Regular') as membership_level_name,
                COALESCE(ml.slug, 'regular') as membership_level_slug
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
