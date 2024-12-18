<?php
namespace CustomerManagement\Models;

abstract class BaseModel {
    protected $wpdb;
    protected $table_name;
    protected $primary_key = 'id';

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . $this->get_table_name();
    }

    abstract protected function get_table_name();

    public function get($id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE {$this->primary_key} = %d",
                $id
            )
        );
    }

    public function get_all($args = []) {
        $defaults = [
            'orderby' => $this->primary_key,
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
            'where' => [],
        ];

        $args = wp_parse_args($args, $defaults);
        $where_clause = $this->build_where_clause($args['where']);
        
        $sql = "SELECT * FROM {$this->table_name}";
        if ($where_clause) {
            $sql .= " WHERE {$where_clause}";
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT {$args['limit']}";
            if ($args['offset'] > 0) {
                $sql .= " OFFSET {$args['offset']}";
            }
        }

        return $this->wpdb->get_results($sql);
    }

    public function create($data) {
        $inserted = $this->wpdb->insert(
            $this->table_name,
            $data,
            $this->get_data_format($data)
        );

        if ($inserted) {
            return $this->wpdb->insert_id;
        }

        return false;
    }

    public function update($id, $data) {
        return $this->wpdb->update(
            $this->table_name,
            $data,
            [$this->primary_key => $id],
            $this->get_data_format($data),
            ['%d']
        );
    }

    public function delete($id) {
        return $this->wpdb->delete(
            $this->table_name,
            [$this->primary_key => $id],
            ['%d']
        );
    }

    public function count($args = []) {
        $where_clause = $this->build_where_clause($args);
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name}";
        if ($where_clause) {
            $sql .= " WHERE {$where_clause}";
        }

        return $this->wpdb->get_var($sql);
    }

    protected function build_where_clause($conditions) {
        if (empty($conditions)) {
            return '';
        }

        $where_parts = [];
        foreach ($conditions as $field => $value) {
            if (is_null($value)) {
                $where_parts[] = "`$field` IS NULL";
            } else {
                $where_parts[] = $this->wpdb->prepare("`$field` = %s", $value);
            }
        }

        return implode(' AND ', $where_parts);
    }

    protected function get_data_format($data) {
        $format = [];
        foreach ($data as $field => $value) {
            if (is_int($value)) {
                $format[] = '%d';
            } elseif (is_float($value)) {
                $format[] = '%f';
            } else {
                $format[] = '%s';
            }
        }
        return $format;
    }
}
