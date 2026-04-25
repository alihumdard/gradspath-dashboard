<?php

namespace Modules\Institutions\app\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;

class ImportUniversitiesCommand extends Command
{
    protected $signature = 'institutions:import-universities
        {path=university.json : Path to the universities JSON file, relative to the project root unless absolute}
        {--dry-run : Validate and count the import without writing to the database}';

    protected $description = 'Import universities from a JSON file into the universities table.';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("University JSON file is not readable: {$path}");

            return self::FAILURE;
        }

        try {
            $universities = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('Invalid university JSON: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! is_array($universities)) {
            $this->error('University JSON must contain an array of university records.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $dryRun = (bool) $this->option('dry-run');

        $bar = $this->output->createProgressBar(count($universities));
        $bar->start();

        foreach ($universities as $record) {
            if (! is_array($record) || ! isset($record['name']) || trim((string) $record['name']) === '') {
                $skipped++;
                $bar->advance();
                continue;
            }

            $values = $this->values($record);

            if ($dryRun) {
                $bar->advance();
                continue;
            }

            if ($this->upsertUniversity($record, $values)) {
                $created++;
            } else {
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('University import dry run completed.');
            $this->line('Valid records: '.(count($universities) - $skipped));
            $this->line("Skipped records: {$skipped}");

            return self::SUCCESS;
        }

        $this->info('University import completed.');
        $this->line("Created: {$created}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function values(array $record): array
    {
        return [
            'name' => trim((string) $record['name']),
            'display_name' => null,
            'country' => (string) ($record['country'] ?? 'US'),
            'alpha_two_code' => $this->nullableString($record['alpha_two_code'] ?? null),
            'city' => null,
            'domains' => $this->stringList($record['domains'] ?? []),
            'web_pages' => $this->stringList($record['web_pages'] ?? []),
            'state_province' => $this->nullableString($record['state-province'] ?? null),
            'logo_url' => null,
            'is_active' => true,
        ];
    }

    private function upsertUniversity(array $record, array $values): bool
    {
        $now = now();
        $databaseValues = $this->databaseValues($values);
        $databaseValues['updated_at'] = $now;

        if (isset($record['id']) && is_numeric($record['id'])) {
            $id = (int) $record['id'];
            $exists = DB::table('universities')->useWritePdo()->where('id', $id)->exists();

            DB::table('universities')->upsert([
                ['id' => $id, 'created_at' => $now, ...$databaseValues],
            ], ['id'], array_keys($databaseValues));

            return ! $exists;
        }

        $exists = DB::table('universities')
            ->useWritePdo()
            ->where('name', $values['name'])
            ->where('country', $values['country'])
            ->exists();

        DB::table('universities')->upsert([
            ['created_at' => $now, ...$databaseValues],
        ], ['name', 'country'], array_keys($databaseValues));

        return ! $exists;
    }

    private function databaseValues(array $values): array
    {
        $values['domains'] = json_encode($values['domains'], JSON_THROW_ON_ERROR);
        $values['web_pages'] = json_encode($values['web_pages'], JSON_THROW_ON_ERROR);

        return $values;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item) => trim($item))
            ->values()
            ->all();
    }
}
