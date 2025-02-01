<?php
/**
 * Customer Detail PDF Template
 * 
 * @package     WP_Customer
 * @subpackage  Views/Templates/Customer/PDF
 * @version     1.0.0
 * 
 * Path: /wp-customer/src/Views/templates/customer/pdf/customer-detail-pdf.php
 */

defined('ABSPATH') || exit;
?>
<html>
<head>
    <style>
        body { 
            font-family: dejavusans; 
            font-size: 10pt;
            line-height: 1.5;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 1px solid #666;
            padding-bottom: 10px;
        }
        .company-name { 
            font-size: 20pt; 
            font-weight: bold;
            margin-bottom: 10px;
        }
        .document-date {
            font-size: 9pt;
            color: #666;
        }
        .info-table { 
            width: 100%; 
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td { 
            padding: 8px;
            vertical-align: top;
        }
        .label { 
            font-weight: bold; 
            width: 150px;
            color: #333;
        }
        .value {
            color: #000;
        }
        
        /* Stats Section */
        .stats-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .stats-table th,
        .stats-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .stats-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8pt;
            text-align: center;
            color: #666;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        
        /* Page Numbers */
        @page {
            footer: html_footer;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">CUSTOMER DETAILS</div>
        <div class="document-date">Generated: <?php echo date('d F Y H:i'); ?></div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Customer Name</td>
            <td class="value">: <?php echo esc_html($customer->name); ?></td>
        </tr>
        <tr>
            <td class="label">Customer Code</td>
            <td class="value">: <?php echo esc_html($customer->code); ?></td>
        </tr>
        <tr>
            <td class="label">Total Branches</td>
            <td class="value">: <?php echo intval($customer->branch_count); ?></td>
        </tr>
        <tr>
            <td class="label">Created Date</td>
            <td class="value">: <?php echo date('d F Y H:i', strtotime($customer->created_at)); ?></td>
        </tr>
        <tr>
            <td class="label">Last Updated</td>
            <td class="value">: <?php echo date('d F Y H:i', strtotime($customer->updated_at)); ?></td>
        </tr>
        <?php if (!empty($customer->npwp)): ?>
        <tr>
            <td class="label">NPWP</td>
            <td class="value">: <?php echo esc_html($customer->npwp); ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($customer->nib)): ?>
        <tr>
            <td class="label">NIB</td>
            <td class="value">: <?php echo esc_html($customer->nib); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <htmlpagefooter name="footer">
        <div class="footer">
            Page {PAGENO} of {nbpg} | Generated by WP Customer
        </div>
    </htmlpagefooter>
</body>
</html>
