<?php
namespace CustomerManagement\Models;

class EmployeeModel extends BaseModel {
    protected function get_table_name() {
        return 'customer_employees';
    }

    public function get_with_customers($id) {
        $sql = $this->wpdb->prepare("
            SELECT 
                e.*,
                COUNT(c.id) as customer_count
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}customers c ON e.id = c.employee_id
            WHERE e.id = %d
            GROUP BY e.id
        ", $id);

        return $this->wpdb->get_row($sql);
    }

    public function get_all_with_customer_count() {
        $sql = "
            SELECT 
                e.*,
                COUNT(c.id) as customer_count
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}customers c ON e.id = c.employee_id
            GROUP BY e.id
            ORDER BY e.name ASC
        ";

        return $this->wpdb->get_results($sql);
    }

    public function get_for_datatable($args = []) {
        $defaults = [
            'start' => 0,
            'length' => 10,
            'search' => '',
            'order_column' => 'name',
            'order_dir' => 'ASC'
        ];

        $args = wp_parse_args($args, $defaults);

        $sql = "
            SELECT 
                e.*,
                COUNT(c.id) as customer_count
            FROM {$this->table_name} e
            LEFT JOIN {$this->wpdb->prefix}customers c ON e.id = c.employee_id
        ";

        if (!empty($args['search'])) {
            $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $sql .= $this->wpdb->prepare("
                WHERE e.name LIKE %s 
                OR e.position LIKE %s
            ", $search, $search);
        }

        $sql .= " GROUP BY e.id";
        $sql .= " ORDER BY {$args['order_column']} {$args['order_dir']}";
        $sql .= " LIMIT {$args['start']}, {$args['length']}";

        return $this->wpdb->get_results($sql);
    }
}
