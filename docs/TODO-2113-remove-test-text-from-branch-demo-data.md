# TODO-2113: Remove 'Test' Text from BranchDemoData.php

## Issue
Teks 'Test Branch' di BranchDemoData.php tidak diperlukan untuk demo ini. Hanya perlu teks 'Branch' saja.

## Target
- [x] Ubah 'Test Branch' menjadi 'Branch' di generateExtraBranchesForTesting method
- [x] Tambah kota (regency) ke nama branch di generateExtraBranches method

## Files to Edit
- `src/Database/Demo/BranchDemoData.php`

## Followup
- Test generate demo data branch untuk memastikan nama branch tidak mengandung 'Test'
