<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pegawai - {{ date('d/m/Y') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.4;
        }

        .header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #4988C4;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
            margin-left: 15px;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #4988C4;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 14pt;
            color: #64748b;
        }

        .report-info {
            text-align: right;
            font-size: 9pt;
            color: #64748b;
        }

        .filters {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8fafc;
            border-left: 4px solid #4988C4;
            font-size: 9pt;
        }

        .filters-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #1e293b;
        }

        .filter-item {
            margin: 3px 0;
            color: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9pt;
        }

        thead {
            background-color: #4988C4;
            color: #ffffff;
        }

        th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #3a6fa5;
        }

        td {
            padding: 8px;
            border: 1px solid #e2e8f0;
        }

        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tbody tr:hover {
            background-color: #f1f5f9;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 500;
        }

        .badge-primary {
            background-color: #4988C4;
            color: #ffffff;
        }

        .badge-danger {
            background-color: #ef4444;
            color: #ffffff;
        }

        .badge-info {
            background-color: #06b6d4;
            color: #ffffff;
        }

        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
            font-size: 8pt;
            color: #64748b;
            text-align: center;
        }

        .summary {
            margin-top: 15px;
            padding: 10px;
            background-color: #f1f5f9;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div style="display: flex; align-items: center;">
                @if(file_exists(public_path('images/logo.png')))
                <img src="{{ public_path('images/logo.png') }}" alt="WorkforceHub" class="logo">
                @endif
                <div class="header-text">
                    <div class="company-name">WorkforceHub</div>
                    <div class="report-title">Laporan Data Pegawai</div>
                </div>
            </div>
            <div class="report-info">
                <div>Tanggal: {{ date('d F Y') }}</div>
                <div>Waktu: {{ date('H:i:s') }}</div>
            </div>
        </div>
    </div>

    @if(count($filters) > 0)
    <div class="filters">
        <div class="filters-title">Filter yang Diterapkan:</div>
        @if(isset($filters['search']))
        <div class="filter-item">• Pencarian: "{{ $filters['search'] }}"</div>
        @endif
        @if(isset($filters['position']))
        <div class="filter-item">• Jabatan: {{ $filters['position'] }}</div>
        @endif
        @if(isset($filters['gender']))
        <div class="filter-item">• Jenis Kelamin: {{ $filters['gender'] }}</div>
        @endif
        @if(isset($filters['birth_date']))
        <div class="filter-item">• Tanggal Lahir: {{ $filters['birth_date'] }}</div>
        @endif
        @if(isset($filters['created_date']))
        <div class="filter-item">• Tanggal Dibuat: {{ $filters['created_date'] }}</div>
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 25%;">Nama Pegawai</th>
                <th style="width: 12%;" class="text-center">Jenis Kelamin</th>
                <th style="width: 12%;" class="text-center">Tanggal Lahir</th>
                <th style="width: 8%;" class="text-center">Usia</th>
                <th style="width: 18%;">Jabatan</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $index => $employee)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $employee->name }}</strong></td>
                <td class="text-center">
                    @if($employee->gender == 'L')
                        <span class="badge badge-primary">Laki-Laki</span>
                    @else
                        <span class="badge badge-danger">Perempuan</span>
                    @endif
                </td>
                <td class="text-center">{{ $employee->birth_date->format('d/m/Y') }}</td>
                <td class="text-center">{{ $employee->birth_date->age }} tahun</td>
                <td>
                    <span class="badge badge-info">{{ $employee->position->name ?? '-' }}</span>
                </td>
                <td>{{ $employee->description ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                    Tidak ada data pegawai yang ditemukan.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        Total Data: {{ $total }} pegawai
    </div>

    <div class="footer">
        <div>Dicetak pada: {{ date('d F Y, H:i:s') }}</div>
        <div>WorkforceHub</div>
    </div>
</body>
</html>

