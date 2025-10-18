# TODO-2160: Invoice Payment Status Filter

**Status:** ✅ Completed
**Tanggal:** 2025-10-18
**Prioritas:** Medium
**Tipe:** Feature Enhancement

## Deskripsi

Menambahkan filter checkbox untuk invoice berdasarkan status pembayaran. Default tampilan adalah invoice yang belum bayar (pending).

### Requirement
- Default value: belum_bayar = 1, yang lain = 0
- Tampilan pertama hanya menampilkan invoice belum bayar
- Jika checkbox lunas di-check, tampil belum lunas + lunas
- Jika checkbox belum lunas di-uncheck, hanya tampil yang lunas
- Dan seterusnya untuk status lainnya

## Solusi

### Status yang Tersedia
1. **pending** - Belum Dibayar (default: checked)
2. **paid** - Lunas (default: unchecked)
3. **overdue** - Terlambat (default: unchecked)
4. **cancelled** - Dibatalkan (default: unchecked)

### File yang Diubah

#### 1. Template: company-invoice-left-panel.php
**Perubahan:** Menambahkan filter checkboxes di atas table

```php
<!-- Filter Status Pembayaran -->
<div class="wi-panel-filters" style="padding: 10px 15px; background: #f5f5f5; border-bottom: 1px solid #ddd;">
    <strong>Filter Status:</strong>
    <label style="margin-left: 10px;">
        <input type="checkbox" id="filter-pending" checked> Belum Dibayar
    </label>
    <label style="margin-left: 10px;">
        <input type="checkbox" id="filter-paid"> Lunas
    </label>
    <label style="margin-left: 10px;">
        <input type="checkbox" id="filter-overdue"> Terlambat
    </label>
    <label style="margin-left: 10px;">
        <input type="checkbox" id="filter-cancelled"> Dibatalkan
    </label>
</div>
```

#### 2. Model: CompanyInvoiceModel.php
**Perubahan:** Menambahkan parameter filter di getDataTableData() dan WHERE clause

**Lines 490-501:** Added default filter parameters
```php
$defaults = [
    'start' => 0,
    'length' => 10,
    'search' => '',
    'order_column' => 'created_at',
    'order_dir' => 'desc',
    'filter_pending' => 1,      // Default checked
    'filter_paid' => 0,
    'filter_overdue' => 0,
    'filter_cancelled' => 0
];
```

**Lines 597-623:** Added payment status filter logic
```php
// Payment Status Filter
$status_filters = [];
if (!empty($params['filter_pending'])) {
    $status_filters[] = 'pending';
}
if (!empty($params['filter_paid'])) {
    $status_filters[] = 'paid';
}
if (!empty($params['filter_overdue'])) {
    $status_filters[] = 'overdue';
}
if (!empty($params['filter_cancelled'])) {
    $status_filters[] = 'cancelled';
}

// If no status selected, show nothing
if (empty($status_filters)) {
    $where_prepared .= " AND 1=0";
} else {
    // Build IN clause for selected statuses
    $status_placeholders = implode(', ', array_fill(0, count($status_filters), '%s'));
    $status_clause = $wpdb->prepare(" AND ci.status IN ($status_placeholders)", $status_filters);
    $where_prepared .= $status_clause;
}
```

#### 3. Controller: CompanyInvoiceController.php
**Perubahan:** Menambahkan handling parameter filter dari AJAX request

**Lines 625-642:** Added filter parameter handling
```php
// Get payment status filters
$filterPending = isset($_POST['filter_pending']) ? intval($_POST['filter_pending']) : 1;
$filterPaid = isset($_POST['filter_paid']) ? intval($_POST['filter_paid']) : 0;
$filterOverdue = isset($_POST['filter_overdue']) ? intval($_POST['filter_overdue']) : 0;
$filterCancelled = isset($_POST['filter_cancelled']) ? intval($_POST['filter_cancelled']) : 0;

// Get data from model
$result = $this->invoice_model->getDataTableData([
    'start' => $start,
    'length' => $length,
    'search' => $search,
    'order_column' => $orderColumn,
    'order_dir' => $orderDir,
    'filter_pending' => $filterPending,
    'filter_paid' => $filterPaid,
    'filter_overdue' => $filterOverdue,
    'filter_cancelled' => $filterCancelled
]);
```

#### 4. JavaScript: company-invoice-datatable-script.js
**Perubahan:** Menambahkan filter parameters ke AJAX data dan event listener

**Lines 65-68:** Added filter parameters to AJAX data
```javascript
data: function(d) {
    return $.extend({}, d, {
        action: 'handle_company_invoice_datatable',
        nonce: wpCustomerData.nonce,
        filter_pending: $('#filter-pending').is(':checked') ? 1 : 0,
        filter_paid: $('#filter-paid').is(':checked') ? 1 : 0,
        filter_overdue: $('#filter-overdue').is(':checked') ? 1 : 0,
        filter_cancelled: $('#filter-cancelled').is(':checked') ? 1 : 0
    });
},
```

**Lines 156-160:** Added checkbox change event handler
```javascript
// Bind filter checkbox events
$('#filter-pending, #filter-paid, #filter-overdue, #filter-cancelled').on('change', function() {
    console.log('Filter changed, reloading table...');
    dataTable.ajax.reload();
});
```

## Hasil

### Behavior Filter
1. **Initial Load:** Hanya tampil invoice dengan status "pending" (Belum Dibayar)
2. **Check Lunas:** Tampil pending + paid
3. **Uncheck Pending:** Hanya tampil paid
4. **Check All:** Tampil semua status
5. **Uncheck All:** Tidak tampil data (empty result)

### Query Logic
- Jika tidak ada checkbox yang di-check: `WHERE 1=0` (no results)
- Jika ada checkbox yang di-check: `WHERE ci.status IN ('pending', 'paid', ...)` sesuai yang di-check

## Testing

1. Load halaman invoice → Verify hanya tampil pending
2. Check checkbox "Lunas" → Verify tampil pending + paid
3. Uncheck "Belum Dibayar" → Verify hanya tampil paid
4. Check "Terlambat" → Verify tampil paid + overdue
5. Uncheck semua → Verify tidak ada data yang tampil

## Catatan

- Filter menggunakan IN clause untuk multiple status
- Default behavior: hanya pending yang tampil
- Filter real-time: langsung reload table saat checkbox berubah
- Jika semua checkbox unchecked: table kosong (no data)
- Compatible dengan search dan pagination existing

## Dependencies

Tidak ada dependency baru. Menggunakan:
- jQuery (existing)
- DataTables (existing)
- WordPress AJAX (existing)
