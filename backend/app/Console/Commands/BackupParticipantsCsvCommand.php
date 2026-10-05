<?php

namespace App\Console\Commands;

use App\Models\FondaRegistration;
use App\Models\NiveaEntry;
use App\Models\RegisteredInvoice;
use App\Models\SabaEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class BackupParticipantsCsvCommand extends Command
{
    protected $signature = 'promos:backup-csv';

    protected $description = 'Envía por correo un CSV con los participantes de cada promo activa como respaldo.';

    private const RECIPIENT = 'jeremy.castillo@supercarnes.com';

    private const MALTA_CAMPAIGN_ID = 6;
    private const SABA_CAMPAIGN_ID  = 7;
    private const NIVEA_CAMPAIGN_ID = 8;
    private const FONDA_CAMPAIGN_ID = 4;

    public function handle(): int
    {
        $now       = now()->setTimezone('America/Panama');
        $timestamp = $now->format('Y-m-d_H-i');
        $label     = $now->format('d/m/Y H:i') . ' (Panamá)';

        $sheets = [
            "malta_vigor_{$timestamp}.csv"  => $this->maltaCsv(),
            "saba_{$timestamp}.csv"         => $this->sabaCsv(),
            "nivea_{$timestamp}.csv"        => $this->niveaCsv(),
            "fonda_{$timestamp}.csv"        => $this->fondaCsv(),
        ];

        $counts = [
            'Malta Vigor' => $this->countLines($sheets["malta_vigor_{$timestamp}.csv"]),
            'Saba'        => $this->countLines($sheets["saba_{$timestamp}.csv"]),
            'Nivea'       => $this->countLines($sheets["nivea_{$timestamp}.csv"]),
            'Fonda'       => $this->countLines($sheets["fonda_{$timestamp}.csv"]),
        ];

        Mail::send([], [], function ($message) use ($sheets, $counts, $label) {
            $message->to(self::RECIPIENT)
                ->subject("Backup participantes promos – {$label}");

            $body  = "<h2>Backup de participantes – {$label}</h2><ul>";
            foreach ($counts as $promo => $count) {
                $body .= "<li><strong>{$promo}:</strong> {$count} " . ($count === 1 ? 'participante' : 'participantes') . '</li>';
            }
            $body .= '</ul><p>Se adjuntan los CSV de cada promoción.</p>';

            $message->html($body);

            foreach ($sheets as $filename => $csv) {
                $message->attachData($csv, $filename, ['mime' => 'text/csv; charset=UTF-8']);
            }
        });

        $summary = collect($counts)->map(fn($c, $p) => "{$p}: {$c}")->implode(', ');
        $this->info("CSV enviado a " . self::RECIPIENT . " — {$summary}");

        return self::SUCCESS;
    }

    private function maltaCsv(): string
    {
        $headers = [
            'id', 'nombre', 'cedula', 'telefono', 'correo', 'sucursal',
            'numero_factura', 'monto', 'fecha_factura', 'puntos', 'estado_validacion',
            'estado_promo', 'registrada_en',
        ];

        $rows = RegisteredInvoice::query()
            ->with(['user', 'branch'])
            ->where('campaign_id', self::MALTA_CAMPAIGN_ID)
            ->orderBy('id')
            ->get()
            ->map(fn($r) => [
                $r->id,
                $r->user?->name ?? '',
                $r->user?->cedula ?? '',
                $r->user?->phone ?? '',
                $r->user?->email ?? '',
                $r->branch?->name ?? '',
                $r->invoice_number ?? '',
                $r->purchase_amount ?? '',
                $r->issued_at?->format('Y-m-d') ?? '',
                $r->points_awarded ?? '',
                $r->validation_status ?? '',
                $r->status ?? '',
                $r->created_at?->setTimezone('America/Panama')->format('Y-m-d H:i') ?? '',
            ]);

        return $this->buildCsv($headers, $rows);
    }

    private function sabaCsv(): string
    {
        $headers = [
            'id', 'nombre', 'cedula', 'telefono', 'correo', 'fecha_nacimiento',
            'historia', 'numero_factura', 'sucursal', 'monto', 'fecha_factura',
            'estado', 'fuente', 'registrada_en',
        ];

        $rows = SabaEntry::query()
            ->with('branch')
            ->where('campaign_id', self::SABA_CAMPAIGN_ID)
            ->orderBy('id')
            ->get()
            ->map(fn($e) => [
                $e->id,
                $e->full_name,
                $e->cedula,
                $e->phone,
                $e->email,
                $e->birthdate?->format('Y-m-d') ?? '',
                $e->story ?? '',
                $e->invoice_number ?? '',
                $e->branch?->name ?? '',
                $e->purchase_amount ?? '',
                $e->issued_at?->format('Y-m-d') ?? '',
                $e->status,
                $e->source ?? '',
                $e->created_at?->setTimezone('America/Panama')->format('Y-m-d H:i') ?? '',
            ]);

        return $this->buildCsv($headers, $rows);
    }

    private function niveaCsv(): string
    {
        $headers = [
            'id', 'nombre', 'cedula', 'telefono', 'correo', 'fecha_nacimiento',
            'mama_postulada', 'historia', 'numero_factura', 'sucursal', 'monto',
            'fecha_factura', 'estado', 'fuente', 'registrada_en',
        ];

        $rows = NiveaEntry::query()
            ->with('branch')
            ->where('campaign_id', self::NIVEA_CAMPAIGN_ID)
            ->orderBy('id')
            ->get()
            ->map(fn($e) => [
                $e->id,
                $e->full_name,
                $e->cedula,
                $e->phone,
                $e->email,
                $e->birthdate?->format('Y-m-d') ?? '',
                $e->nominee_name ?? '',
                $e->story ?? '',
                $e->invoice_number ?? '',
                $e->branch?->name ?? '',
                $e->purchase_amount ?? '',
                $e->issued_at?->format('Y-m-d') ?? '',
                $e->status,
                $e->source ?? '',
                $e->created_at?->setTimezone('America/Panama')->format('Y-m-d H:i') ?? '',
            ]);

        return $this->buildCsv($headers, $rows);
    }

    private function fondaCsv(): string
    {
        $headers = [
            'id', 'codigo', 'nombre', 'cedula', 'telefono', 'correo',
            'nombre_fonda', 'ubicacion_fonda', 'plato', 'descripcion',
            'estado', 'registrada_en',
        ];

        $rows = FondaRegistration::query()
            ->where('campaign_id', self::FONDA_CAMPAIGN_ID)
            ->orderBy('id')
            ->get()
            ->map(fn($r) => [
                $r->id,
                $r->code ?? '',
                $r->full_name,
                $r->cedula,
                $r->phone,
                $r->email,
                $r->fonda_name ?? '',
                $r->fonda_location ?? '',
                $r->dish_name ?? '',
                $r->description ?? '',
                $r->status,
                $r->created_at?->setTimezone('America/Panama')->format('Y-m-d H:i') ?? '',
            ]);

        return $this->buildCsv($headers, $rows);
    }

    private function buildCsv(array $headers, \Illuminate\Support\Collection $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        // BOM UTF-8 para que Excel lo abra correctamente
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function countLines(string $csv): int
    {
        // Líneas menos 1 (cabecera), descartando la línea vacía final
        $lines = array_filter(explode("\n", $csv));
        return max(0, count($lines) - 1);
    }
}
