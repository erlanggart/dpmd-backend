#!/usr/bin/env python3
"""
Script untuk membersihkan path file di desk_bumdes2025.json
Menghapus prefix folder uploads/dokumen_bumdes/ dan laporan_keuangan/
"""

import json
import re
import os

def clean_file_path(path):
    """
    Membersihkan path file dengan menghapus prefix folder
    """
    if not path:
        return path
    
    # Hapus prefix uploads/dokumen_bumdes/
    if path.startswith('uploads/dokumen_bumdes/'):
        return path.replace('uploads/dokumen_bumdes/', '')
    
    # Hapus prefix laporan_keuangan/
    if path.startswith('laporan_keuangan/'):
        return path.replace('laporan_keuangan/', '')
    
    return path

def clean_bumdes_data(data):
    """
    Membersihkan data BUMDes dengan menghapus prefix folder dari path file
    """
    if isinstance(data, dict):
        # Jika ada key laporan_keuangan
        if 'laporan_keuangan' in data:
            laporan_keuangan = data['laporan_keuangan']
            if isinstance(laporan_keuangan, dict):
                for key, value in laporan_keuangan.items():
                    if isinstance(value, str):
                        laporan_keuangan[key] = clean_file_path(value)
        
        # Bersihkan field file lainnya jika ada
        file_fields = [
            'FileBadanHukum', 'FilePerdes', 'FileSK', 'FileNIB', 'FileLKPP', 
            'FileNPWP', 'FileLaporanKeuangan', 'FileAkta'
        ]
        
        for field in file_fields:
            if field in data and isinstance(data[field], str):
                data[field] = clean_file_path(data[field])
    
    return data

def main():
    """
    Fungsi utama untuk membersihkan file JSON
    """
    input_file = 'desk_bumdes2025.json'
    backup_file = 'desk_bumdes2025_backup.json'
    
    try:
        # Backup file asli
        if os.path.exists(input_file):
            print(f"Membuat backup: {backup_file}")
            with open(input_file, 'r', encoding='utf-8') as f:
                original_data = f.read()
            with open(backup_file, 'w', encoding='utf-8') as f:
                f.write(original_data)
        
        # Baca file JSON
        print(f"Membaca file: {input_file}")
        with open(input_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        print(f"Total data BUMDes: {len(data)}")
        
        # Bersihkan data
        cleaned_count = 0
        for i, bumdes in enumerate(data):
            original_bumdes = json.dumps(bumdes, sort_keys=True)
            cleaned_bumdes = clean_bumdes_data(bumdes)
            if json.dumps(cleaned_bumdes, sort_keys=True) != original_bumdes:
                cleaned_count += 1
                print(f"Membersihkan data BUMDes #{i+1}: {bumdes.get('namabumdesa', 'Unknown')}")
        
        # Simpan file yang sudah dibersihkan
        print(f"Menyimpan file yang sudah dibersihkan: {input_file}")
        with open(input_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=4, ensure_ascii=False)
        
        print(f"\n✅ Selesai!")
        print(f"📊 Total data yang dibersihkan: {cleaned_count}")
        print(f"💾 File backup: {backup_file}")
        print(f"✨ File bersih: {input_file}")
        
    except FileNotFoundError:
        print(f"❌ Error: File {input_file} tidak ditemukan!")
    except json.JSONDecodeError as e:
        print(f"❌ Error: File JSON tidak valid - {e}")
    except Exception as e:
        print(f"❌ Error tidak terduga: {e}")

if __name__ == "__main__":
    main()
