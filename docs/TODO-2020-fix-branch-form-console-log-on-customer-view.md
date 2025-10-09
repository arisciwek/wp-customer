# TODO-2020: Fix Branch Form Console Log Appearing on Customer View

## Issue
When clicking the view button on the Customer datatable, console logs from branch forms appear, even though the branch form is on the second tab. The logs should only appear when the branch tab is clicked.

## Root Cause
Branch form scripts (create-branch-form.js and edit-branch-form.js) are initialized globally on document ready, causing methods and console logs to execute whenever the customer page loads, regardless of the active tab.

## Target
Implement lazy initialization for branch forms so that their methods and console logs only execute when the branch tab is actively clicked, not during initial customer view loading.

## Files
- assets/js/customer/customer-script.js (call form init only on branch tab click)
- assets/js/branch/create-branch-form.js (added initialized flag to prevent auto-init)
- assets/js/branch/edit-branch-form.js (added initialized flag to prevent auto-init)

## Status
Completed
