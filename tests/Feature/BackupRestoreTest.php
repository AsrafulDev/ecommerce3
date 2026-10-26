<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DefaultDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultDatabaseSeeder::class);
        $this->actingAs(User::first(), 'admin');
    }

    /** @param array<string, array<int, array<string, mixed>>> $tables */
    private function backupZip(array $tables): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bk').'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($tables as $table => $rows) {
            $zip->addFromString("data/{$table}.json", json_encode($rows));
        }
        $zip->close();

        return new UploadedFile($path, 'backup.zip', 'application/zip', null, true);
    }

    public function test_an_old_column_name_is_mapped_and_unknown_columns_are_dropped(): void
    {
        $this->post(route('backup.restore'), [
            'backup_file' => $this->backupZip([
                'categories' => [[
                    'id' => 9001, 'name' => 'Restored Category', 'slug' => 'restored-category',
                    'status' => 1, 'parent_id' => 0, 'front_view' => 0, 'image' => 'public/uploads/x.png',
                    'meta_decription' => 'Old spelling in the backup', 'legacy_column' => 'not on this database',
                ]],
            ]),
        ]);

        $row = DB::table('categories')->where('id', 9001)->first();
        $this->assertNotNull($row, 'the row is restored');
        $this->assertSame('Old spelling in the backup', $row->meta_description);
    }

    public function test_a_table_that_fails_to_restore_keeps_its_existing_rows(): void
    {
        DB::table('categories')->insert(['id' => 500, 'name' => 'Keep Me', 'slug' => 'keep-me', 'status' => 1, 'parent_id' => 0, 'front_view' => 0, 'image' => 'public/uploads/k.png']);
        $existing = DB::table('categories')->count();
        $this->assertGreaterThan(0, $existing, 'the database has categories to protect');

        // Two rows with the same id: the second insert fails, so the whole table must roll back.
        $this->post(route('backup.restore'), [
            'backup_file' => $this->backupZip([
                'categories' => [
                    ['id' => 9100, 'name' => 'Duplicate A', 'slug' => 'dup-a', 'status' => 1, 'parent_id' => 0],
                    ['id' => 9100, 'name' => 'Duplicate B', 'slug' => 'dup-b', 'status' => 1, 'parent_id' => 0],
                ],
            ]),
        ]);

        $this->assertSame($existing, DB::table('categories')->count(), 'the existing categories are still there');
    }
}
