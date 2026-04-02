<?php

namespace Tests\Unit;

use App\Services\PhotoPathResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoPathResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_returns_null_when_photo_is_empty(): void
    {
        $this->assertNull(PhotoPathResolver::resolve(null));
    }

    public function test_resolve_returns_same_url_for_external_photo(): void
    {
        $url = 'https://example.com/avatar.png';

        $this->assertSame($url, PhotoPathResolver::resolve($url));
    }

    public function test_resolve_returns_asset_for_storage_path(): void
    {
        $path = 'storage/profile.png';

        $this->assertStringContainsString('storage/profile.png', PhotoPathResolver::resolve($path));
    }

    public function test_resolve_prefixes_storage_for_relative_paths(): void
    {
        $path = 'profile.png';

        $this->assertStringContainsString('storage/profile.png', PhotoPathResolver::resolve($path));
    }
}
