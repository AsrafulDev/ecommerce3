<?php

namespace Tests\Feature;

use App\Support\Release\CustomPackages;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The release commands: the custom package names, the build's checks, and the upload's checks.
 */
class ReleaseCommandsTest extends TestCase
{
    public function test_a_custom_package_takes_a_folder_name_inside_a_release(): void
    {
        $this->assertSame('softmit-bd-double-entry', CustomPackages::folderName('softmit/bd-double-entry'));
        $this->assertArrayHasKey('softmit/bd-double-entry', CustomPackages::all());
    }

    public function test_a_release_version_must_look_like_a_version(): void
    {
        $this->artisan('release:build', ['version' => 'two'])->assertExitCode(1);
    }

    public function test_a_dry_run_shows_the_plan_and_writes_nothing(): void
    {
        $zip = storage_path('app/updates/update-9.8.7.zip');
        File::delete($zip);

        $this->artisan('release:build', ['version' => '9.8.7', '--dry-run' => true])
            ->expectsOutputToContain('packages/softmit-bd-double-entry')
            ->assertExitCode(0);

        $this->assertFalse(File::exists($zip));
    }

    public function test_upload_needs_a_built_release(): void
    {
        File::delete(storage_path('app/updates/update-9.8.6.zip'));

        $this->artisan('release:upload', ['version' => '9.8.6', '--secret' => 'x'])->assertExitCode(1);
    }

    public function test_upload_needs_the_secret(): void
    {
        $this->artisan('release:upload', ['version' => '9.8.5'])->assertExitCode(1);
    }
}
