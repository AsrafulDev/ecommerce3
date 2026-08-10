<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * End-to-end HTTP tests for the Media Gallery (folder-wise file manager).
 * Runs without DB (media controller is pure filesystem).
 * Test files are created under public/uploads/media and cleaned up.
 */
class MediaControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();

        // The master layout reads Auth::guard('admin')->user()->image etc.
        // Provide a fake admin (no DB involved) so the page renders in tests.
        $admin = new \App\Models\User();
        $admin->id = 1;
        $admin->name = 'Test Admin';
        $admin->email = 'admin@test.local';
        $admin->image = 'public/uploads/default/avatar.png';
        $this->actingAs($admin, 'admin');

        $this->root = public_path('uploads/media');
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    /** Remove any leftover test artifacts. */
    protected function cleanup(): void
    {
        foreach (['__test', '__src', '__dst', '__img', '__renamed', '__moved'] as $d) {
            $p = $this->root.'/'.$d;
            if (is_dir($p)) {
                $this->rrmdir($p);
            }
            foreach (['jpg', 'png', 'pdf'] as $ext) {
                $f = $this->root.'/'.$d.'.'.$ext;
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
        foreach (['hello.png', 'doc.pdf', 'picker.png', 'fake.jpg', 'evil.php'] as $f) {
            $p = $this->root.'/'.$f;
            if (is_file($p)) {
                @unlink($p);
            }
        }
    }

    protected function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $p = $dir.'/'.$item;
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    /**
     * The full admin layout needs DB (permissions / settings), which is not
     * reachable in the sandbox — so we assert on the controller's returned
     * view + data instead of rendering the master layout.
     */
    public function test_index_controller_returns_view(): void
    {
        $controller = new \App\Http\Controllers\Admin\MediaController();
        $response = $controller->index(\Illuminate\Http\Request::create('/admin/media'));

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
        $this->assertEquals('backEnd.media.index', $response->name());

        $data = $response->getData();
        $this->assertIsArray($data['folders']);
        $this->assertIsArray($data['files']);
        $this->assertIsArray($data['breadcrumbs']);
        $this->assertSame('', $data['path']);
    }

    public function test_create_folder(): void
    {
        $res = $this->post('/admin/media/folder/create', ['path' => '', 'folder_name' => '__test']);
        $res->assertStatus(302);
        $this->assertDirectoryExists($this->root.'/__test');

        // duplicate fails gracefully (still 302, no error)
        $res = $this->post('/admin/media/folder/create', ['path' => '', 'folder_name' => '__test']);
        $res->assertStatus(302);
    }

    public function test_rename_folder(): void
    {
        mkdir($this->root.'/__test', 0775, true);
        $res = $this->post('/admin/media/folder/rename', ['path' => '__test', 'new_name' => '__renamed']);
        $res->assertStatus(302);
        $this->assertDirectoryExists($this->root.'/__renamed');
        $this->assertDirectoryDoesNotExist($this->root.'/__test');
    }

    public function test_delete_folder_recursively(): void
    {
        mkdir($this->root.'/__test/sub', 0775, true);
        file_put_contents($this->root.'/__test/sub/file.jpg', 'x');
        $res = $this->post('/admin/media/folder/delete', ['path' => '__test']);
        $res->assertStatus(302);
        $this->assertDirectoryDoesNotExist($this->root.'/__test');
    }

    public function test_upload_image(): void
    {
        $file = UploadedFile::fake()->image('hello.png');
        $res = $this->post('/admin/media/upload', ['path' => '', 'files' => [$file]]);
        $res->assertStatus(302);
        $this->assertFileExists($this->root.'/hello.png');
    }

    public function test_upload_pdf(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $res = $this->post('/admin/media/upload', ['path' => '', 'files' => [$file]]);
        $res->assertStatus(302);
        $this->assertFileExists($this->root.'/doc.pdf');
    }

    public function test_upload_blocked_non_allowed(): void
    {
        $php = UploadedFile::fake()->create('evil.php', 100, 'application/x-php');
        $res = $this->post('/admin/media/upload', ['path' => '', 'files' => [$php]]);
        $res->assertStatus(302);
        $this->assertFileDoesNotExist($this->root.'/evil.php');
    }

    public function test_upload_blocked_fake_extension(): void
    {
        // A php payload masquerading as .jpg — safeMime should reject (mime is php)
        $file = UploadedFile::fake()->create('fake.jpg', 100, 'application/x-php');
        $res = $this->post('/admin/media/upload', ['path' => '', 'files' => [$file]]);
        $res->assertStatus(302);
        $this->assertFileDoesNotExist($this->root.'/fake.jpg');
    }

    public function test_rename_file_keeps_extension(): void
    {
        copy(__DIR__.'/../stubs/test-image.png', $this->root.'/__img.png');
        $res = $this->post('/admin/media/file/rename', ['path' => '__img.png', 'new_name' => '__renamed']);
        $res->assertStatus(302);
        $this->assertFileExists($this->root.'/__renamed.png');
        $this->assertFileDoesNotExist($this->root.'/__img.png');
    }

    public function test_delete_file(): void
    {
        copy(__DIR__.'/../stubs/test-image.png', $this->root.'/__img.png');
        $res = $this->post('/admin/media/file/delete', ['path' => '__img.png']);
        $res->assertStatus(302);
        $this->assertFileDoesNotExist($this->root.'/__img.png');
    }

    public function test_move_file_to_folder(): void
    {
        mkdir($this->root.'/__dst', 0775, true);
        copy(__DIR__.'/../stubs/test-image.png', $this->root.'/__img.png');

        $res = $this->post('/admin/media/move', [
            'items'  => ['__img.png'],
            'target' => '__dst',
        ]);
        $res->assertStatus(302);
        $this->assertFileExists($this->root.'/__dst/__img.png');
        $this->assertFileDoesNotExist($this->root.'/__img.png');
    }

    public function test_copy_file_to_folder(): void
    {
        mkdir($this->root.'/__dst', 0775, true);
        copy(__DIR__.'/../stubs/test-image.png', $this->root.'/__img.png');

        $res = $this->post('/admin/media/copy', [
            'items'  => ['__img.png'],
            'target' => '__dst',
        ]);
        $res->assertStatus(302);
        $this->assertFileExists($this->root.'/__dst/__img.png');
        $this->assertFileExists($this->root.'/__img.png'); // original kept
    }

    public function test_move_folder(): void
    {
        mkdir($this->root.'/__src', 0775, true);
        file_put_contents($this->root.'/__src/a.jpg', 'x');
        mkdir($this->root.'/__dst', 0775, true);

        $res = $this->post('/admin/media/move', [
            'items'  => ['__src'],
            'target' => '__dst',
        ]);
        $res->assertStatus(302);
        $this->assertDirectoryExists($this->root.'/__dst/__src');
        $this->assertFileExists($this->root.'/__dst/__src/a.jpg');
    }

    public function test_picker_content(): void
    {
        mkdir($this->root.'/__test', 0775, true);
        copy(__DIR__.'/../stubs/test-image.png', $this->root.'/__test/pic.png');

        $res = $this->get('/admin/media/picker?path=__test');
        $res->assertStatus(200);
        $res->assertSee('pic.png');
    }

    public function test_picker_upload(): void
    {
        $file = UploadedFile::fake()->image('picker.png');
        $res = $this->post('/admin/media/picker/upload', ['path' => '', 'files' => [$file]]);
        $res->assertStatus(200);
        $this->assertFileExists($this->root.'/picker.png');
        $res->assertJson(['ok' => true]);
    }

    public function test_path_traversal_blocked(): void
    {
        $res = $this->post('/admin/media/folder/create', ['path' => '../../', 'folder_name' => 'evil']);
        // Should NOT create anything outside media root; expect 403
        $this->assertTrue(in_array($res->status(), [302, 403]));
        $this->assertDirectoryDoesNotExist(base_path('public/evil'));
        $this->assertDirectoryDoesNotExist(base_path('evil'));
    }
}
