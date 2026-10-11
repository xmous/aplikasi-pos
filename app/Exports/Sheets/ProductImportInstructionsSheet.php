<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportInstructionsSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['PETUNJUK IMPORT BARANG'],
            ['1', 'Gunakan sheet "Import Barang". Jangan mengubah nama kolom pada baris pertama.'],
            ['2', 'Hapus baris contoh sebelum mengunggah data sebenarnya.'],
            ['3', 'Kolom wajib: sku, nama_barang, kategori, satuan_dasar, harga_beli, dan harga_jual.'],
            ['4', 'Kategori boleh diisi dengan kode atau nama yang tersedia pada sheet Referensi.'],
            ['5', 'Satuan boleh diisi dengan simbol atau nama yang tersedia pada sheet Referensi.'],
            ['6', 'Kolom YA/TIDAK: kelola_stok, punya_kedaluwarsa, dan aktif.'],
            ['7', 'SKU digunakan sebagai kunci pembaruan. SKU yang sama akan diperbarui jika opsi pembaruan diaktifkan.'],
            ['8', 'Harga konversi boleh dikosongkan. Sistem menghitung harga dasar × faktor konversi.'],
            ['9', 'Maksimal tiga konversi satuan per baris. Konversi tambahan dapat diatur melalui menu Edit Barang.'],
            ['10', 'Satuan dasar barang yang sudah memiliki histori tidak dapat diubah melalui impor.'],
            ['11', 'Impor diproses per baris. Baris gagal tidak membatalkan baris lain yang valid.'],
            ['12', 'Maksimal 1.000 baris dan ukuran file maksimal 2 MB dalam sekali impor.'],
        ];
    }

    public function title(): string
    {
        return 'Petunjuk';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('173F75');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A1:B13')->getAlignment()->setVertical('top');
        $sheet->getStyle('B:B')->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('B')->setWidth(105);

        return [];
    }
}
