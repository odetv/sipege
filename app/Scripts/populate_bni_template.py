import sys
import json
import os
import shutil
import win32com.client

def populate_bni_xls(template_path, output_path, data_json_path):
    if not os.path.exists(template_path):
        raise FileNotFoundError(f"Template not found: {template_path}")
    
    with open(data_json_path, 'r', encoding='utf-8') as f:
        payload = json.load(f)

    if isinstance(payload, dict):
        items = payload.get('items', [])
        remark1 = payload.get('remark1', '')
        remark2 = payload.get('remark2', '')
        tgl_transaksi = payload.get('tgl_transaksi', '')
        rek_debet = payload.get('rek_debet', '')
        total_records = payload.get('total_records', len(items))
        total_amount = payload.get('total_amount', 0)
        timestamp_creation = payload.get('timestamp_creation', '')
    else:
        items = payload
        remark1 = ''
        remark2 = ''
        tgl_transaksi = ''
        rek_debet = ''
        total_records = len(items)
        total_amount = sum(float(it.get('amount', 0) or 0) for it in items)
        timestamp_creation = ''

    # Ensure output dir
    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    
    # Copy template to output
    shutil.copyfile(template_path, output_path)
    
    abs_output = os.path.abspath(output_path)
    
    excel = win32com.client.DispatchEx("Excel.Application")
    excel.Visible = False
    excel.DisplayAlerts = False
    
    wb = None
    try:
        wb = excel.Workbooks.Open(abs_output)
        ws = wb.Sheets("Inhouse")
        
        # Update metadata headers if provided
        if timestamp_creation:
            ws.Range("A6").Value = str(timestamp_creation)
        
        ws.Range("A8").Value = "P"
        if tgl_transaksi:
            ws.Range("B8").NumberFormat = "@"
            ws.Range("B8").Value = str(tgl_transaksi)
        if rek_debet:
            ws.Range("C8").NumberFormat = "@"
            ws.Range("C8").Value = str(rek_debet)
        ws.Range("D8").Value = int(total_records)
        ws.Range("E8").Value = float(total_amount)
        
        # Clear rows 10 to 5000 across columns A to T
        ws.Range("A10:T5000").ClearContents()
        
        row = 10
        for item in items:
            amount = float(item.get('amount', 0) or 0)
            rek = str(item.get('rek_tujuan', '') or '').strip()
            nama = str(item.get('nama', '') or '').strip()
            email = str(item.get('email', '') or '').strip()
            item_rem1 = str(item.get('remark1', '') or remark1).strip()
            item_rem2 = str(item.get('remark2', '') or remark2).strip()

            if amount <= 0 and not rek and not nama:
                continue
            
            # Col 1 (A): Rek. Tujuan(16)
            ws.Cells(row, 1).NumberFormat = "@"
            ws.Cells(row, 1).Value = rek
            
            # Col 2 (B): Nama Penerima(40)
            ws.Cells(row, 2).Value = nama[:40]
            
            # Col 3 (C): Amount
            ws.Cells(row, 3).Value = amount
            
            # Col 4 (D): Remark1(33)
            ws.Cells(row, 4).Value = item_rem1[:33]
            
            # Col 5 (E): Remark2(50)
            ws.Cells(row, 5).Value = item_rem2[:50]
            
            # Col 17 (Q): EMAIL FLAG(1) & Col 18 (R): Email(100)
            has_email = '@' in email and '.' in email
            ws.Cells(row, 17).Value = "Y" if has_email else "N"
            ws.Cells(row, 18).Value = email[:100] if has_email else ""
            
            # Col 20 (T): FLAG(1) -> 'N'
            ws.Cells(row, 20).Value = "N"
            
            row += 1
            
        wb.Save()
    finally:
        if wb is not None:
            try:
                wb.Close(False)
            except Exception:
                pass
        try:
            excel.Quit()
        except Exception:
            pass

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: python populate_bni_template.py <template_path> <output_path> <data_json_path>")
        sys.exit(1)
    try:
        populate_bni_xls(sys.argv[1], sys.argv[2], sys.argv[3])
        print("SUCCESS")
    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        sys.exit(1)
