<?php
namespace CustomerManagement\Models;

class BranchModel extends BaseModel {
    protected function get_table_name() {
        return 'customer_branches';
    }

    public function get_with_customers($id) {
        $sql = $this->wpdb->prepare("
            SELECT 
                b.*,
                COUNT(c.id) as customer_count
            FROM {$this->table_name} b
            LEFT JOIN {$this->wpdb->prefix}customers c ON b.id = c.branch_id
            WHERE b.id = %d
            GROUP BY b.id
        ", $id);

        return $this->wpdb->get_row($sql);
    }

    public function get_all_with_customer_count() {
        $sql = "
            SELECT 
                b.*,
                COUNT(c.id) as customer_count
            FROM {$this->table_name} b
            LEFT JOIN {$this->wpdb->prefix}customers c ON b.id = c.branch_id
            GROUP BY b.id
            ORDER BY b.name ASC
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
                b.*,
                COUNT(c.id) as customer_count
            FROM {$this->table_name} b
            LEFT JOIN {$this->wpdb->prefix}customers c ON b.id = c.branch_id
        ";

        if (!empty($args['search'])) {
            $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $sql .= $this->wpdb->prepare("
                WHERE b.name LIKE %s 
                OR b.location LIKE %s
            ", $search, $search);
        }

        $sql .= " GROUP BY b.id";
        $sql .= " ORDER BY {$args['order_column']} {$args['order_dir']}";
        $sql .= " LIMIT {$args['start']}, {$args['length']}";

        return $this->wpdb->get_results($sql);
    }
}
