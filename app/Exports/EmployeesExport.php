<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $employees;
    protected $rowNumber = 0;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->employees;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama Pegawai',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Usia',
            'Jabatan',
            'Keterangan',
            'Tanggal Dibuat',
        ];
    }

    /**
     * @param mixed $employee
     * @return array
     */
    public function map($employee): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $employee->name,
            $employee->gender == 'L' ? 'Laki-Laki' : 'Perempuan',
            $employee->birth_date->format('d/m/Y'),
            $employee->birth_date->age . ' tahun',
            $employee->position->name ?? '-',
            $employee->description ?? '-',
            $employee->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4988C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // No
            'B' => 30,  // Nama Pegawai
            'C' => 15,  // Jenis Kelamin
            'D' => 15,  // Tanggal Lahir
            'E' => 10,  // Usia
            'F' => 25,  // Jabatan
            'G' => 40,  // Keterangan
            'H' => 20,  // Tanggal Dibuat
        ];
    }
}

