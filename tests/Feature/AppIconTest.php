<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_links_the_app_icons(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('href="/app-icons/icon.svg"', false)
            ->assertSee('href="/app-icons/apple-touch-icon.png"', false);
    }

    public function test_every_manifest_icon_exists_in_the_app_icons_folder(): void
    {
        /** @var array{icons: list<array{src: string}>} $manifest */
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertNotEmpty($manifest['icons']);

        foreach ($manifest['icons'] as $icon) {
            $this->assertStringStartsWith('/app-icons/', $icon['src']);
            $this->assertFileExists(public_path($icon['src']));
        }

        $this->assertFileExists(public_path('app-icons/apple-touch-icon.png'));
        $this->assertDirectoryDoesNotExist(public_path('icons'));
        $this->assertDirectoryDoesNotExist(public_path('icon'));
    }
}
